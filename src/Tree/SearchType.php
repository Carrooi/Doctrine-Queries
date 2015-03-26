<?php

namespace Carrooi\Doctrine\Queries\Tree;

/**
 *
 * @author David Kudera
 */
class SearchType
{


	const SEARCH_FOR_SAME = 1;

	const SEARCH_IN_PARENTS = 2;

	const SEARCH_IN_CHILDREN = 4;

	const SEARCH_EVERYWHERE = 15;


	const CONDITION_AND = 'and';

	const CONDITION_OR = 'or';

}
