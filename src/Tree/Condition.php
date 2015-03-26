<?php

namespace Carrooi\Doctrine\Queries\Tree;

/**
 *
 * @author David Kudera
 */
class Condition
{

	private $condition;

	private $parameters;

	public function __construct($conditions, array $parameters)
	{
		$this->condition = $conditions;
		$this->parameters = $parameters;
	}

	public function getCondition()
	{
		return $this->condition;
	}

	public function getParameters()
	{
		return $this->parameters;
	}

}
