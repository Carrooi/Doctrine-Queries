<?php

namespace Carrooi\Doctrine\Queries;

use Doctrine\ORM\AbstractQuery;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject as BaseQueryObject;

/**
 *
 * @author David Kudera
 */
abstract class QueryObject extends BaseQueryObject
{


	/** @var \Kdyby\Doctrine\EntityRepository */
	private $repository;

	/** @var callable[] */
	private $filters = [];

	/** @var callable[] */
	private $selectFilters = [];

	/** @var array */
	private $selects = [];

	/** @var string[] */
	private $joins = [];


	/**
	 * @param \Kdyby\Doctrine\EntityRepository $repository
	 */
	public function __construct(EntityRepository $repository)
	{
		$this->repository = $repository;
	}


	/**
	 * @return \Kdyby\Doctrine\EntityRepository
	 */
	public function getRepository()
	{
		return $this->repository;
	}


	/********************************* HELPERS *********************************/


	/**
	 * @param \Kdyby\Doctrine\QueryBuilder $qb
	 * @param array $parameters
	 * @return $this
	 */
	protected function addParameters(QueryBuilder $qb, array $parameters)
	{
		foreach ($parameters as $key => $value) {
			$qb->setParameter($key, $value);
		}

		return $this;
	}


	/********************************* FILTERS MANIPULATION *********************************/


	/**
	 * @param callable $filter
	 * @return $this
	 */
	protected function addFilter(callable $filter)
	{
		$this->filters[] = $filter;
		return $this;
	}


	/**
	 * @param callable $selectFilter
	 * @return $this
	 */
	protected function addSelectFilter(callable $selectFilter)
	{
		$this->selectFilters[] = $selectFilter;
		return $this;
	}


	/********************************* FILTERS APPLYING *********************************/


	/**
	 * @param \Kdyby\Doctrine\QueryBuilder $qb
	 * @return $this
	 */
	protected function applyFilters(QueryBuilder $qb)
	{
		if (count($this->joins) > 0) {
			foreach ($this->joins as $join) {
				$qb->{$join['type']}($join['join'], $join['alias'], $join['conditionType'], $join['condition']);
			}
		}

		foreach ($this->filters as $modifier) {
			$modifier($qb);
		}

		return $this;
	}


	/**
	 * @param \Kdyby\Doctrine\QueryBuilder $qb
	 * @return $this
	 */
	protected function applySelectFilters(QueryBuilder $qb)
	{
		if (count($this->selects) > 0) {
			$selects = [];
			foreach ($this->selects as $alias => $data) {
				$columns = array_unique($data['columns']);
				$distinct = $data['distinct'] ? 'DISTINCT ' : '';

				if (empty($columns)) {
					$selects[] = $distinct. $alias;
				} else {
					if (!in_array('id', $columns)) {
						array_unshift($columns, 'id');
					}

					$columns = implode(',', $columns);

					$selects[] = $distinct. 'PARTIAL '. $alias. '.{'. $columns. '}';
				}
			}

			$qb->select(implode(', ', $selects));
		}

		foreach ($this->selectFilters as $modifier) {
			$modifier($qb);
		}

		return $this;
	}


	/**
	 * @param \Kdyby\Doctrine\QueryBuilder $qb
	 * @return $this
	 */
	protected function applyAllFilters(QueryBuilder $qb)
	{
		$this
			->applyFilters($qb)
			->applySelectFilters($qb);

		return $this;
	}


	/********************************* SELECTS *********************************/


	/**
	 * @param string $alias
	 * @param array $columns
	 * @param bool $distinct
	 * @return $this
	 */
	private function trySilentSelect($alias, array $columns, $distinct = false)
	{
		if (!isset($this->selects[$alias])) {
			$this->selects[$alias] = [
				'distinct' => $distinct,
				'columns' => [],
			];
		}

		$this->selects[$alias]['columns'] = array_merge($this->selects[$alias]['columns'], $columns);

		return $this;
	}


	/**
	 * @param string $alias
	 * @param array $columns
	 * @return $this
	 */
	protected function trySelect($alias, array $columns = [])
	{
		return $this->trySilentSelect($alias, $columns, false);
	}


	/**
	 * @param string $alias
	 * @param array $columns
	 * @return $this
	 */
	protected function tryDistinctSelect($alias, array $columns = [])
	{
		return $this->trySilentSelect($alias, $columns, true);
	}


	/********************************* JOINS *********************************/


	/**
	 * @param string $type
	 * @param string $join
	 * @param string $alias
	 * @param string $conditionType
	 * @param string $condition
	 * @throws \Carrooi\Doctrine\Queries\InvalidArgumentException
	 * @return $this
	 */
	private function trySilentJoin($type, $join, $alias, $conditionType = null, $condition = null)
	{
		switch ($type) {
			case 'inner': $type = 'innerJoin'; break;
			case 'left': $type = 'leftJoin'; break;
			default:
				throw new InvalidArgumentException('Unknown join type '. $type);
				break;
		}

		$name = md5($type. $join. $alias);

		if (array_key_exists($name, $this->joins)) {
			return $this;
		}

		$this->joins[$name] = [
			'type' => $type,
			'join' => $join,
			'alias' => $alias,
			'conditionType' => $conditionType,
			'condition' => $condition,
		];

		return $this;
	}


	/**
	 * @param string $join
	 * @param string $alias
	 * @param string $conditionType
	 * @param string $condition
	 * @return $this
	 */
	protected function tryJoin($join, $alias, $conditionType = null, $condition = null)
	{
		return $this->trySilentJoin('inner', $join, $alias, $conditionType, $condition);
	}


	/**
	 * @param string $join
	 * @param string $alias
	 * @param string $conditionType
	 * @param string $condition
	 * @return $this
	 */
	protected function tryLeftJoin($join, $alias, $conditionType = null, $condition = null)
	{
		return $this->trySilentJoin('left', $join, $alias, $conditionType, $condition);
	}


	/********************************* ACCESSING DATA *********************************/


	/**
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getQueryBuilder()
	{
		return $this->doCreateQuery($this->repository);
	}


	/**
	 * @return \Kdyby\Doctrine\ResultSet
	 */
	public function getResultSet()
	{
		return $this
			->repository
			->fetch($this);
	}


	/**
	 * @param int $hydrationMode
	 * @return array
	 */
	public function getResult($hydrationMode = AbstractQuery::HYDRATE_OBJECT)
	{
		return $this->getQueryBuilder()
			->getQuery()
			->getResult($hydrationMode);
	}


	/**
	 * @param string $value
	 * @param string $key
	 * @return array
	 */
	public function getPairs($value, $key = 'id')
	{
		$data = $this->getResult(AbstractQuery::HYDRATE_ARRAY);
		$result = [];

		foreach ($data as $line) {
			$result[$line[$key]] = $line[$value];
		}

		return $result;
	}


	/**
	 * @param int $hydrationMode
	 * @return object
	 */
	public function getOneOrNullResult($hydrationMode = null)
	{
		return $this
			->getQueryBuilder()
			->getQuery()
			->getOneOrNullResult($hydrationMode);
	}


	/**
	 * @return mixed
	 */
	public function getSingleScalarResult()
	{
		return $this
			->getQueryBuilder()
			->getQuery()
			->getSingleScalarResult();
	}

}
