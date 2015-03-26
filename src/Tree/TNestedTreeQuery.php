<?php

namespace Carrooi\Doctrine\Queries\Tree;

use Carrooi\Doctrine\Queries\InvalidArgumentException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;

/**
 *
 * @author David Kudera
 */
trait TNestedTreeQuery
{


	/**
	 * @return \Kdyby\Doctrine\EntityRepository
	 */
	abstract public function getRepository();


	/**
	 * @param array $mapping
	 * @return array
	 */
	private function getMappingConfiguration(array $mapping)
	{
		$mapping['level'] = isset($mapping['level']) ? $mapping['level'] : 'level';
		$mapping['left'] = isset($mapping['left']) ? $mapping['left'] : 'left';
		$mapping['right'] = isset($mapping['right']) ? $mapping['right'] : 'right';
		$mapping['root'] = isset($mapping['root']) ? $mapping['root'] : 'root';
		$mapping['id'] = isset($mapping['id']) ? $mapping['id'] : 'id';

		return $mapping;
	}


	/**
	 * @param \Doctrine\ORM\Mapping\ClassMetadata $metadata
	 * @param string $alias
	 * @param object $entity
	 * @param array $mapping
	 * @return array
	 */
	private function getSearchSameCondition(ClassMetadata $metadata, $alias, $entity, array $mapping)
	{
		$id = $metadata->getFieldValue($entity, $mapping['id']);

		return [
			'condition' => "$alias.$mapping[id] = :id_{$alias}_$id",
			'parameters' => [
				"id_{$alias}_$id" => $id,
			],
		];
	}


	/**
	 * @param \Doctrine\ORM\Mapping\ClassMetadata $metadata
	 * @param string $alias
	 * @param object $entity
	 * @param array $mapping
	 * @return array
	 */
	private function getSearchInParentsCondition(ClassMetadata $metadata, $alias, $entity, array $mapping)
	{
		$id = $metadata->getFieldValue($entity, $mapping['id']);

		return [
			'condition' => "$alias.$mapping[level] > :level_{$alias}_$id AND $alias.$mapping[id] > :left_{$alias}_$id AND $alias.$mapping[id] < :right_{$alias}_$id",
			'parameters' => [
				"level_{$alias}_$id" => $metadata->getFieldValue($entity, $mapping['level']),
				"left_{$alias}_$id" => $metadata->getFieldValue($entity, $mapping['left']),
				"right_{$alias}_$id" => $metadata->getFieldValue($entity, $mapping['right']),
			],
		];
	}


	/**
	 * @param \Doctrine\ORM\Mapping\ClassMetadata $metadata
	 * @param string $alias
	 * @param object $entity
	 * @param array $mapping
	 * @return array
	 */
	private function getSearchInChildrenCondition(ClassMetadata $metadata, $alias, $entity, array $mapping)
	{
		$id = $metadata->getFieldValue($entity, $mapping['id']);

		return [
			'condition' => "$alias.$mapping[level] < :level_{$alias}_$id AND $alias.$mapping[root] = :root_{$alias}_$id AND $alias.$mapping[left] < :id_{$alias}_$id AND $alias.$mapping[right] > :id_{$alias}_$id",
			'parameters' => [
				"id_{$alias}_$id" => $id,
				"root_{$alias}_$id" => $metadata->getFieldValue($entity, $mapping['root']),
				"level_{$alias}_$id" => $metadata->getFieldValue($entity, $mapping['level']),
			],
		];
	}


	/**
	 * @param int $type
	 * @param object $entity
	 * @param string $alias
	 * @param array $mapping
	 * @return array
	 */
	private function createConditionForEntity($type, $entity, $alias, array $mapping)
	{
		$em = $this->getRepository()->getEntityManager();
		$metadata = $em->getClassMetadata(get_class($entity));

		$result = [
			'condition' => [],
			'parameters' => [],
		];

		if ($type & SearchType::SEARCH_FOR_SAME) {
			$condition = $this->getSearchSameCondition($metadata, $alias, $entity, $mapping);
			$result['condition'][] = $condition['condition'];
			$result['parameters'] = array_merge($result['parameters'], $condition['parameters']);
		}

		if ($type & SearchType::SEARCH_IN_PARENTS) {
			$condition = $this->getSearchInParentsCondition($metadata, $alias, $entity, $mapping);
			$result['condition'][] = $condition['condition'];
			$result['parameters'] = array_merge($result['parameters'], $condition['parameters']);
		}

		if ($type & SearchType::SEARCH_IN_CHILDREN) {
			$condition = $this->getSearchInChildrenCondition($metadata, $alias, $entity, $mapping);
			$result['condition'][] = $condition['condition'];
			$result['parameters'] = array_merge($result['parameters'], $condition['parameters']);
		}

		$result['condition'] = (new Orx)->addMultiple($result['condition']);

		return $result;
	}


	/**
	 * @param array $entities
	 * @param $alias
	 * @param string $conditionType
	 * @param int $type
	 * @param array $mapping
	 * @return \Carrooi\Doctrine\Queries\Tree\Condition
	 */
	protected function createNestedTreeSearchCondition(array $entities, $alias, $conditionType = SearchType::CONDITION_OR, $type = SearchType::SEARCH_EVERYWHERE, array $mapping = [])
	{
		if ($conditionType === null) {
			$conditionType = SearchType::CONDITION_OR;
		}

		if ($type === null) {
			$type = SearchType::SEARCH_EVERYWHERE;
		}

		$mapping = $this->getMappingConfiguration($mapping);

		$condition = [
			'condition' => [],
			'parameters' => [],
		];

		foreach ($entities as $entity) {
			$entityCondition = $this->createConditionForEntity($type, $entity, $alias, $mapping);

			$condition['condition'][] = $entityCondition['condition'];
			$condition['parameters'] = array_merge($condition['parameters'], $entityCondition['parameters']);
		}

		switch ($conditionType) {
			case SearchType::CONDITION_OR:
				$expr = new Orx;
				break;
			case SearchType::CONDITION_AND:
				$expr = new Andx;
				break;
			default:
				throw new InvalidArgumentException('Unknown condition type '. $conditionType. '.');
				break;
		}

		$condition['condition'] = $expr->addMultiple($condition['condition']);

		return new Condition($condition['condition'], $condition['parameters']);
	}

}
