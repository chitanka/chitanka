<?php

namespace Chitanka\LibBundle\Entity;

class BookRevisionRepository extends RevisionRepository
{
	public function getQueryBuilder($orderBys = null)
	{
		$qb = parent::getQueryBuilder($orderBys)
			->select('r', 'b', 'a', 's', 'c')
			->from($this->getEntityName(), 'r')
			->leftJoin('r.book', 'b')
			->leftJoin('b.authors', 'a')
			->leftJoin('b.sequence', 's')
			->leftJoin('b.category', 'c');

		return $qb;
	}

}
