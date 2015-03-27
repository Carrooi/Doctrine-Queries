# Carrooi/DoctrineQueries

[![Build Status](https://img.shields.io/travis/Carrooi/Doctrine-Queries.svg?style=flat-square)](https://travis-ci.org/Carrooi/Doctrine-Queries)
[![Donate](https://img.shields.io/badge/donate-PayPal-brightgreen.svg?style=flat-square)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=F3SNC38XAUP56)

Builder for doctrine query builders based on kdyby/doctrine

## Installation

```
$ composer require carrooi/doctrine-queries
```

## QueryObject

Please, first read documentation of kdyby's [QueryObjects](https://github.com/Kdyby/Doctrine/blob/master/docs/en/resultset.md#queryobject).

```php
class UserQuery extends Carrooi\Doctrine\Queries\QueryObject
{

	public function byId($id)
	{
		$this->addFilter(function(QueryBuilder $qb) use ($id) {
			$qb->andWhere('u.id = :id')->setParameter('id', $id);
		});
		
		return $this;
	}
	
	public function doCreateQuery(Queryable $repository)
	{
		$qb = $repository->createQueryBuilder()
			->select('u')->from('App\User', 'u');
			
		$this
			->applyFilters($qb)
			->applySelectFilters($qb);
			
		// or just:
		// $this->applyAllFilters($qb);
		
		return $qb;
	}

}
```

### Select filters

```php
class UserQuery extends Carrooi\Doctrine\Queries\QueryObject
{

	public function selectCount()
	{
		$this->addSelectFilter(function(QueryBuilder $qb) {
			$qb->select('COUNT(u)');
		});
		
		return $this;
	}

}
```

### Selects

If you have more methods which selects different columns, you will run into errors about already selected columns. 
You can avoid that by using some helper methods.

```php
class UserQuery extends Carrooi\Doctrine\Queries\QueryObject
{

	public function selectNick()
	{
		$this->trySelect('u', ['nick']);
		return $this;
	}
	
	public function selectEmail()
	{
		$this->trySelect('u', ['email']);
		return $this;
	}

}
```

**DQL:** `SELECT PARTIAL u.{id,nick,email} FROM ...`

With result alias:

```php
class UserQuery extends Carrooi\Doctrine\Queries\QueryObject
{

	public function selectNickAndEmail()
	{
		$this->trySelect('u', ['user' => ['nick', 'email']]);
		return $this;
	}

}
```

**DQL:** `SELECT PARTIAL u.{id,nick,email} AS user FROM ...`

Or with distinct:

```php
class UserQuery extends Carrooi\Doctrine\Queries\QueryObject
{

	public function selectNick()
	{
		$this->tryDistinctSelect('u', ['nick']);
		return $this;
	}

}
```

You can also use classic column selects without partials. That can be useful for example for array hydration.

```php
class UserQuery extends Carrooi\Doctrine\Queries\QueryObject
{

	public function selectNick()
	{
		$this->trySelect('u', [
			'nick' => 'nickAlias',				// nickAlias will be name of result key
		]);
		return $this;
	}
	
	public function selectEmail()
	{
		$this->trySelect('u', ['email']); 		// you can combine partial and classic column selects
		return $this;
	}

}
```

**DQL:** `SELECT u.nick AS nickAlias, PARTIAL u.{id,email} FROM ...`

### Joins

Same problem like with selects is with joins. If you will try to join same relation many times, you will get error.
Again, there are methods for that.

```php
class UserQuery extends Carrooi\Doctrine\Queries\QueryObject
{

	public function byBookName($name)
	{
		$this->tryJoin('u.books', 'b');		// INNER JOIN
		
		$this->addFilter(function(QueryBuilder $qb) use ($name) {
			$qb->andWhere('b.name = :name')->setParameter('name', $name);
		});
		
		return $this;
	}

}
```

You can also use `tryLeftJoin` method.

### Helpers

* `$query->addParameters(QueryBuilder $qb, array $parameters)`: set parameters without overwriting the old ones

### Nested trees searching

If you are using eg. gedmo nested trees, you could also use `TNestedTreeQuery` trait for simple searching in tree.

```php
class UserQuery extends Carrooi\Doctrine\Queries\QueryObject
{

	use Carrooi\Doctrine\Queries\Tree\TNestedTreeQuery;
	
	public function byTree(array $entities)
	{
		// ... some joins
		
		$this->addFilter(function(QueryBuilder $qb) use ($entities) {
			$condition = $this->createNestedTreeSearchCondition($entities, 'entityAlias');
			
			$qb->andWhere($condition->getCondition());
            $query->addParameters($qb, $condition->getParameters());
		});
	}

}
```

That example will find all entities in database with at least one entity from given array of entities, even they are 
same, in some children entity or some parent entity.

**Search by at least one entity (uses OR)** `default`

```php
use Carrooi\Doctrine\Queries\Tree\SearchType;

$query->createNestedTreeSearchCondition($entities, 'entityAlias', SearchType::CONDITION_OR);
```

**Search by all entities (uses AND)**

```php
use Carrooi\Doctrine\Queries\Tree\SearchType;

$query->createNestedTreeSearchCondition($entities, 'entityAlias', SearchType::CONDITION_AND);
```

**Search only for same, in parents and in children** `default`

```php
use Carrooi\Doctrine\Queries\Tree\SearchType;

$query->createNestedTreeSearchCondition($entities, 'entityAlias', null, SearchType::SEARCH_EVERYWHERE);
```

**Search only for same**

```php
use Carrooi\Doctrine\Queries\Tree\SearchType;

$query->createNestedTreeSearchCondition($entities, 'entityAlias', null, SearchType::SEARCH_FOR_SAME);
```

**Search only in parents**

```php
use Carrooi\Doctrine\Queries\Tree\SearchType;

$query->createNestedTreeSearchCondition($entities, 'entityAlias', null, SearchType::SEARCH_IN_PARENTS);
```

**Search only in children**

```php
use Carrooi\Doctrine\Queries\Tree\SearchType;

$query->createNestedTreeSearchCondition($entities, 'entityAlias', null, SearchType::SEARCH_IN_CHILDREN);
```

**Combined searching**

```php
use Carrooi\Doctrine\Queries\Tree\SearchType;

$query->createNestedTreeSearchCondition($entities, 'entityAlias', null, SearchType::SEARCH_IN_PARENTS | SearchType::SEARCH_IN_CHILDREN);
```

**Custom column names**

`TNestedTreeQuery` trait will use by default these column names:

* `id`
* `level`
* `root`
* `left`
* `right`

But if you need, you can use custom names:

```php
use Carrooi\Doctrine\Queries\Tree\SearchType;

$query->createNestedTreeSearchCondition($entities, 'entityAlias', null, null, [
	'id' => 'id',
	'level' => 'lvl',
	'root' => 'root',
	'left' => 'lft',
	'right' => 'rgt',
]);
```

### Getting results

* `getQueryBuilder()`
* `getResultSet()`
* `getResult()`
* `getPairs()`
* `getOneOrNullResult()`
* `getSingleScalarResult()`

## Changelog

* 1.1.0
	+ Add field function for DQL
	+ Add TNestedTreeQuery trait for searching in nested trees

* 1.0.1
	+ Do not rewrite existing joins

* 1.0.0
	+ Initial version
