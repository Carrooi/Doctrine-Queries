# Carrooi/DoctrineQueries

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

### Selects

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

### Advanced selects

There is one problem with example above and that is.
