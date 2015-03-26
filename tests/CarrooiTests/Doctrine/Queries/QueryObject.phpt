<?php

/**
 * Test: Carrooi\Doctrine\Queries\QueryObject
 *
 * @testCase CarrooiTests\Doctrine\Queries\QueryObjectTest
 * @author David Kudera
 */

namespace CarrooiTests\Doctrine\Queries;

use CarrooiTests\Model\QueryMock;
use Kdyby\Doctrine\Dql\Join;
use Kdyby\Doctrine\QueryBuilder;
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
		$query = new QueryMock($this->repository);

		$query->addFilter(function(QueryBuilder $qb) {
			$qb->andWhere('id = :id')->setParameter('id', 5);
		});

		Assert::same('SELECT a FROM App\Entity a WHERE id = :id', $query->getQueryBuilder()->getDQL());
	}


	public function testAddSelectFilter()
	{
		$query = new QueryMock($this->repository);

		$query->addSelectFilter(function(QueryBuilder $qb) {
			$qb->select('COUNT(a)');
		});

		Assert::same('SELECT COUNT(a) FROM App\Entity a', $query->getQueryBuilder()->getDQL());
	}


	public function testTrySelect()
	{
		$query = new QueryMock($this->repository);

		$query->trySelect('a');

		Assert::same('SELECT a FROM App\Entity a', $query->getQueryBuilder()->getDQL());
	}


	public function testTrySelect_partial()
	{
		$query = new QueryMock($this->repository);

		$query->trySelect('a', ['name', 'title']);

		Assert::same('SELECT PARTIAL a.{id,name,title} FROM App\Entity a', $query->getQueryBuilder()->getDQL());
	}


	public function testTrySelect_partial_moreCalls()
	{
		$query = new QueryMock($this->repository);

		$query->trySelect('a', ['name']);
		$query->trySelect('a', ['title']);

		Assert::same('SELECT PARTIAL a.{id,name,title} FROM App\Entity a', $query->getQueryBuilder()->getDQL());
	}


	public function testTryDistinctSelect()
	{
		$query = new QueryMock($this->repository);

		$query->tryDistinctSelect('a');

		Assert::same('SELECT DISTINCT a FROM App\Entity a', $query->getQueryBuilder()->getDQL());
	}


	public function testTryDistinctSelect_partial()
	{
		$query = new QueryMock($this->repository);

		$query->tryDistinctSelect('a', ['name']);

		Assert::same('SELECT DISTINCT PARTIAL a.{id,name} FROM App\Entity a', $query->getQueryBuilder()->getDQL());
	}


	public function testTryDistinctSelect_partial_moreCalls()
	{
		$query = new QueryMock($this->repository);

		$query->tryDistinctSelect('a', ['name']);
		$query->tryDistinctSelect('a', ['title']);

		Assert::same('SELECT DISTINCT PARTIAL a.{id,name,title} FROM App\Entity a', $query->getQueryBuilder()->getDQL());
	}


	public function testTryJoin()
	{
		$query = new QueryMock($this->repository);

		$query->tryJoin('a.user', 'u');

		Assert::same('SELECT a FROM App\Entity a INNER JOIN a.user u', $query->getQueryBuilder()->getDQL());
	}


	public function testTryJoin_moreCalls()
	{
		$query = new QueryMock($this->repository);

		$query->tryJoin('a.user', 'u');
		$query->tryJoin('a.user', 'u');

		Assert::same('SELECT a FROM App\Entity a INNER JOIN a.user u', $query->getQueryBuilder()->getDQL());
	}


	public function testTryJoin_advanced()
	{
		$query = new QueryMock($this->repository);

		$query->tryJoin('App\User', 'u', Join::WITH, 'u = a.user');

		Assert::same('SELECT a FROM App\Entity a INNER JOIN App\User u WITH u = a.user', $query->getQueryBuilder()->getDQL());
	}


	public function testTryLeftJoin()
	{
		$query = new QueryMock($this->repository);

		$query->tryLeftJoin('a.user', 'u');

		Assert::same('SELECT a FROM App\Entity a LEFT JOIN a.user u', $query->getQueryBuilder()->getDQL());
	}

}


run(new QueryObjectTest);
