<?php

/**
 * Test: Carrooi\Doctrine\Queries\QueryObject
 *
 * @testCase CarrooiTests\Doctrine\Queries\QueryObjectTest
 * @author David Kudera
 */

namespace CarrooiTests\Doctrine\Queries;

use Carrooi\Doctrine\Queries\QueryObject;
use Kdyby\Doctrine\Dql\Join;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Persistence\Queryable;
use Mockery;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class QueryObjectTest extends TestCase
{


	/** @var \Kdyby\Doctrine\EntityRepository */
	private $repository;


	public function setUp()
	{
		$em = Mockery::mock('Kdyby\Doctrine\EntityManager')->makePartial();

		$this->repository = Mockery::mock('Kdyby\Doctrine\EntityRepository')
			->shouldReceive('createQueryBuilder')->once()->andReturn($em->createQueryBuilder())
			->getMock();
	}


	public function tearDown()
	{
		Mockery::close();
	}


	public function testAddFilter()
	{
		$query = new Query($this->repository);

		$query->addFilter(function(QueryBuilder $qb) {
			$qb->andWhere('id = :id')->setParameter('id', 5);
		});

		Assert::same('SELECT a FROM App\Entity a WHERE id = :id', $query->getQueryBuilder()->getDQL());
	}


	public function testAddSelectFilter()
	{
		$query = new Query($this->repository);

		$query->addSelectFilter(function(QueryBuilder $qb) {
			$qb->select('COUNT(a)');
		});

		Assert::same('SELECT COUNT(a) FROM App\Entity a', $query->getQueryBuilder()->getDQL());
	}


	public function testTrySelect()
	{
		$query = new Query($this->repository);

		$query->trySelect('a', ['name', 'title']);

		Assert::same('SELECT PARTIAL a.{id,name,title} FROM App\Entity a', $query->getQueryBuilder()->getDQL());
	}


	public function testTrySelect_moreCalls()
	{
		$query = new Query($this->repository);

		$query->trySelect('a', ['name']);
		$query->trySelect('a', ['title']);

		Assert::same('SELECT PARTIAL a.{id,name,title} FROM App\Entity a', $query->getQueryBuilder()->getDQL());
	}


	public function testTryDistinctSelect()
	{
		$query = new Query($this->repository);

		$query->tryDistinctSelect('a', ['name']);

		Assert::same('SELECT DISTINCT PARTIAL a.{id,name} FROM App\Entity a', $query->getQueryBuilder()->getDQL());
	}


	public function testTryDistinctSelect_moreCalls()
	{
		$query = new Query($this->repository);

		$query->tryDistinctSelect('a', ['name']);
		$query->tryDistinctSelect('a', ['title']);

		Assert::same('SELECT DISTINCT PARTIAL a.{id,name,title} FROM App\Entity a', $query->getQueryBuilder()->getDQL());
	}


	public function testTryJoin()
	{
		$query = new Query($this->repository);

		$query->tryJoin('a.user', 'u');

		Assert::same('SELECT a FROM App\Entity a INNER JOIN a.user u', $query->getQueryBuilder()->getDQL());
	}


	public function testTryJoin_moreCalls()
	{
		$query = new Query($this->repository);

		$query->tryJoin('a.user', 'u');
		$query->tryJoin('a.user', 'u');

		Assert::same('SELECT a FROM App\Entity a INNER JOIN a.user u', $query->getQueryBuilder()->getDQL());
	}


	public function testTryJoin_advanced()
	{
		$query = new Query($this->repository);

		$query->tryJoin('App\User', 'u', Join::WITH, 'u = a.user');

		Assert::same('SELECT a FROM App\Entity a INNER JOIN App\User u WITH u = a.user', $query->getQueryBuilder()->getDQL());
	}


	public function testTryLeftJoin()
	{
		$query = new Query($this->repository);

		$query->tryLeftJoin('a.user', 'u');

		Assert::same('SELECT a FROM App\Entity a LEFT JOIN a.user u', $query->getQueryBuilder()->getDQL());
	}

}


/**
 *
 * @author David Kudera
 */
class Query extends QueryObject
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


run(new QueryObjectTest);
