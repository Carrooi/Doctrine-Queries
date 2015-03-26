<?php

/**
 * Test: Carrooi\Doctrine\Queries\Tree\TNestedTreeQuery
 *
 * @testCase CarrooiTests\Doctrine\Queries\Tree\NestedTreeQueryTest
 * @author David Kudera
 */

namespace CarrooiTests\Doctrine\Queries\Tree;

use Carrooi\Doctrine\Queries\Tree\SearchType;
use CarrooiTests\Model\NestedTreeQueryMock;
use Kdyby\Doctrine\QueryBuilder;
use Mockery;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class NestedTreeQueryTest extends TestCase
{


	/** @var \Kdyby\Doctrine\EntityRepository */
	private $repository;

	/** @var array */
	private $entities;


	public function __construct()
	{
		$this->entities = [
			'simple' => [
				(object) [
					'id' => 6,
					'root' => 1,
					'level' => 3,
					'left' => 7,
					'right' => 23,
				]
			],
			'mapping' => [
				(object) [
					'_id_' => 6,
					'_root_' => 1,
					'_level_' => 3,
					'_left_' => 7,
					'_right_' => 23,
				]
			],
			'many' => [
				(object) [
					'id' => 6,
					'root' => 1,
					'level' => 3,
					'left' => 7,
					'right' => 23,
				],
				(object) [
					'id' => 52,
					'root' => 4,
					'level' => 5,
					'left' => 53,
					'right' => 198,
				],
			],
		];
	}


	public function setUp()
	{
		$metadata = Mockery::mock('Doctrine\ORM\Mapping\ClassMetadata')
			->shouldReceive('getFieldValue')->andReturnUsing(function($entity, $column) {
				return $entity->{$column};
			})->getMock();

		$em = Mockery::mock('Kdyby\Doctrine\EntityManager')->makePartial()
			->shouldReceive('getClassMetadata')->andReturn($metadata)
			->getMock();

		$this->repository = Mockery::mock('Kdyby\Doctrine\EntityRepository')
			->shouldAllowMockingProtectedMethods()
			->shouldReceive('getEntityManager')->andReturn($em)
			->shouldReceive('createQueryBuilder')->once()->andReturn($em->createQueryBuilder())
			->getMock();
	}


	public function tearDown()
	{
		Mockery::close();
	}


	public function testCreateNestedTreeSearchCondition_searchEverywhere()
	{
		$query = new NestedTreeQueryMock($this->repository);

		$condition = $query->createNestedTreeSearchCondition($this->entities['simple'], 'a');

		$query->addFilter(function(QueryBuilder $qb) use ($query, $condition) {
			$qb->andWhere($condition->getCondition());
			$query->addParameters($qb, $condition->getParameters());
		});

		$dql = 'SELECT a FROM App\Entity a WHERE a.id = :id_6 OR (a.level > :level_6 AND a.id > :left_6 AND a.id < :right_6) OR (a.level < :level_6 AND a.root = :root_6 AND a.left < :id_6 AND a.right > :id_6)';
		$qb = $query->getQueryBuilder();

		Assert::same(6, $qb->getParameter('id_6')->getValue());
		Assert::same(1, $qb->getParameter('root_6')->getValue());
		Assert::same(3, $qb->getParameter('level_6')->getValue());
		Assert::same(7, $qb->getParameter('left_6')->getValue());
		Assert::same(23, $qb->getParameter('right_6')->getValue());

		Assert::same($dql, $qb->getDQL());
	}


	public function testCreateNestedTreeSearchCondition_searchEverywhere_differentMapping()
	{
		$query = new NestedTreeQueryMock($this->repository);

		$condition = $query->createNestedTreeSearchCondition($this->entities['mapping'], 'a', null, SearchType::SEARCH_EVERYWHERE, [
			'id' => '_id_',
			'root' => '_root_',
			'level' => '_level_',
			'left' => '_left_',
			'right' => '_right_',
		]);

		$query->addFilter(function(QueryBuilder $qb) use ($query, $condition) {
			$qb->andWhere($condition->getCondition());
			$query->addParameters($qb, $condition->getParameters());
		});

		$dql = 'SELECT a FROM App\Entity a WHERE a._id_ = :id_6 OR (a._level_ > :level_6 AND a._id_ > :left_6 AND a._id_ < :right_6) OR (a._level_ < :level_6 AND a._root_ = :root_6 AND a._left_ < :id_6 AND a._right_ > :id_6)';
		$qb = $query->getQueryBuilder();

		Assert::same(6, $qb->getParameter('id_6')->getValue());
		Assert::same(1, $qb->getParameter('root_6')->getValue());
		Assert::same(3, $qb->getParameter('level_6')->getValue());
		Assert::same(7, $qb->getParameter('left_6')->getValue());
		Assert::same(23, $qb->getParameter('right_6')->getValue());

		Assert::same($dql, $qb->getDQL());
	}


	public function testCreateNestedTreeSearchCondition_searchSame()
	{
		$query = new NestedTreeQueryMock($this->repository);

		$condition = $query->createNestedTreeSearchCondition($this->entities['simple'], 'a', null, SearchType::SEARCH_FOR_SAME);

		$query->addFilter(function(QueryBuilder $qb) use ($query, $condition) {
			$qb->andWhere($condition->getCondition());
			$query->addParameters($qb, $condition->getParameters());
		});

		$dql = 'SELECT a FROM App\Entity a WHERE a.id = :id_6';
		$qb = $query->getQueryBuilder();

		Assert::same(6, $qb->getParameter('id_6')->getValue());

		Assert::same($dql, $qb->getDQL());
	}


	public function testCreateNestedTreeSearchCondition_searchSame_manyOr()
	{
		$query = new NestedTreeQueryMock($this->repository);

		$condition = $query->createNestedTreeSearchCondition($this->entities['many'], 'a', null, SearchType::SEARCH_FOR_SAME);

		$query->addFilter(function(QueryBuilder $qb) use ($query, $condition) {
			$qb->andWhere($condition->getCondition());
			$query->addParameters($qb, $condition->getParameters());
		});

		$dql = 'SELECT a FROM App\Entity a WHERE a.id = :id_6 OR a.id = :id_52';
		$qb = $query->getQueryBuilder();

		Assert::same(6, $qb->getParameter('id_6')->getValue());

		Assert::same($dql, $qb->getDQL());
	}


	public function testCreateNestedTreeSearchCondition_searchSame_manyAnd()
	{
		$query = new NestedTreeQueryMock($this->repository);

		$condition = $query->createNestedTreeSearchCondition($this->entities['many'], 'a', SearchType::CONDITION_AND, SearchType::SEARCH_FOR_SAME);

		$query->addFilter(function(QueryBuilder $qb) use ($query, $condition) {
			$qb->andWhere($condition->getCondition());
			$query->addParameters($qb, $condition->getParameters());
		});

		$dql = 'SELECT a FROM App\Entity a WHERE a.id = :id_6 AND a.id = :id_52';
		$qb = $query->getQueryBuilder();

		Assert::same(6, $qb->getParameter('id_6')->getValue());

		Assert::same($dql, $qb->getDQL());
	}


	public function testCreateNestedTreeSearchCondition_searchInParents()
	{
		$query = new NestedTreeQueryMock($this->repository);

		$condition = $query->createNestedTreeSearchCondition($this->entities['simple'], 'a', null, SearchType::SEARCH_IN_PARENTS);

		$query->addFilter(function(QueryBuilder $qb) use ($query, $condition) {
			$qb->andWhere($condition->getCondition());
			$query->addParameters($qb, $condition->getParameters());
		});

		$dql = 'SELECT a FROM App\Entity a WHERE a.level > :level_6 AND a.id > :left_6 AND a.id < :right_6';
		$qb = $query->getQueryBuilder();

		Assert::same(3, $qb->getParameter('level_6')->getValue());
		Assert::same(7, $qb->getParameter('left_6')->getValue());
		Assert::same(23, $qb->getParameter('right_6')->getValue());

		Assert::same($dql, $qb->getDQL());
	}


	public function testCreateNestedTreeSearchCondition_searchInChildren()
	{
		$query = new NestedTreeQueryMock($this->repository);

		$condition = $query->createNestedTreeSearchCondition($this->entities['simple'], 'a', null, SearchType::SEARCH_IN_CHILDREN);

		$query->addFilter(function(QueryBuilder $qb) use ($query, $condition) {
			$qb->andWhere($condition->getCondition());
			$query->addParameters($qb, $condition->getParameters());
		});

		$dql = 'SELECT a FROM App\Entity a WHERE a.level < :level_6 AND a.root = :root_6 AND a.left < :id_6 AND a.right > :id_6';
		$qb = $query->getQueryBuilder();

		Assert::same(6, $qb->getParameter('id_6')->getValue());
		Assert::same(1, $qb->getParameter('root_6')->getValue());
		Assert::same(3, $qb->getParameter('level_6')->getValue());

		Assert::same($dql, $qb->getDQL());
	}

}


run(new NestedTreeQueryTest);
