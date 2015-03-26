<?php

namespace CarrooiTests\Model;

use Carrooi\Doctrine\Queries\QueryObject;
use Kdyby\Persistence\Queryable;

/**
 *
 * @author David Kudera
 */
class QueryMock extends QueryObject
{


	/** @var callable */
	private $_doCreateQuery;


	/**
	 * @param callable $doCreateQuery
	 */
	public function setDoCreateQuery(callable $doCreateQuery)
	{
		$this->_doCreateQuery = $doCreateQuery;
	}


	/**
	 * @param \Kdyby\Persistence\Queryable $repository
	 * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
	 */
	protected function doCreateQuery(Queryable $repository)
	{
		$qb = $repository->createQueryBuilder()
			->select('a')->from('App\Entity', 'a');

		if ($this->_doCreateQuery) {
			call_user_func($this->_doCreateQuery, $qb);
		} else {
			$this->applyAllFilters($qb);
		}

		return $qb;
	}


	/**
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		if (method_exists($this, $name)) {
			return call_user_func_array([$this, $name], $args);
		} else {
			return parent::__call($name, $args);
		}
	}

}
