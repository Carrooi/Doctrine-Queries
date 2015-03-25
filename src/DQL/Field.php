<?php

namespace Carrooi\Doctrine\Queries\DQL;

use Carrooi\Doctrine\Queries\NotImplementedException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 *
 * @see https://github.com/fprochazka/project-archivist/blob/master/libs/Archivist/Doctrine/Dql/Field.php
 *
 * @author David Kudera
 * @author Filip Procházka <filip@prochazka.su>
 * @author Jeremy Hicks <jeremy.hicks@gmail.com>
 */
class Field extends FunctionNode
{


	/** @var \Doctrine\ORM\Query\AST\AggregateExpression|\Doctrine\ORM\Query\AST\Functions\FunctionNode|\Doctrine\ORM\Query\AST\InputParameter */
	private $field;

	/** @var \Doctrine\ORM\Query\AST\AggregateExpression[]|\Doctrine\ORM\Query\AST\Functions\FunctionNode[]|\Doctrine\ORM\Query\AST\InputParameter[] */
	private $values = array();


	/**
	 * @param \Doctrine\ORM\Query\Parser $parser
	 */
	public function parse(Parser $parser)
	{
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);

		$this->field = $parser->ArithmeticPrimary();

		$lexer = $parser->getLexer();

		while (count($this->values) < 1 || $lexer->lookahead['type'] != Lexer::T_CLOSE_PARENTHESIS) {
			$parser->match(Lexer::T_COMMA);
			$this->values[] = $parser->ArithmeticPrimary();
		}

		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}


	/**
	 * @param \Doctrine\ORM\Query\SqlWalker $sqlWalker
	 * @return string
	 */
	public function getSql(SqlWalker $sqlWalker)
	{
		$platform = $sqlWalker->getConnection()->getDatabasePlatform();

		if ($platform instanceof PostgreSqlPlatform) {
			return $this->getPostgreSql($sqlWalker);

		} elseif ($platform instanceof MySqlPlatform) {
			return $this->getMysqlSql($sqlWalker);

		}

		throw new NotImplementedException;
	}


	/**
	 * @param \Doctrine\ORM\Query\SqlWalker $sqlWalker
	 * @return string
	 */
	private function getMysqlSql(SqlWalker $sqlWalker)
	{
		$query = 'FIELD(';
		$query .= $this->field->dispatch($sqlWalker);
		$query .= ',';

		for ($i = 0; $i < count($this->values); $i++) {
			if ($i > 0) {
				$query .= ',';
			}

			$query .= $this->values[$i]->dispatch($sqlWalker);
		}

		$query .= ')';

		return $query;
	}


	/**
	 * @param \Doctrine\ORM\Query\SqlWalker $sqlWalker
	 * @return string
	 */
	private function getPostgreSql(SqlWalker $sqlWalker)
	{
		$query = '(CASE';

		for ($i = 1; $i <= count($this->values); $i++) {
			$query .= ' WHEN (' . $this->field->dispatch($sqlWalker) . ') = ' .
			$this->values[$i - 1]->dispatch($sqlWalker) . ' THEN ' . $i;
		}

		$query .= ' END)';

		return $query;
	}

}
