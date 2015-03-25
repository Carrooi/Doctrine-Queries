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

### Getting results

* `getQueryBuilder()`
* `getResultSet()`
* `getResult()`
* `getPairs()`
* `getOneOrNullResult()`
* `getSingleScalarResult()`

## Changelog

* 1.0.0
	+ Initial version
