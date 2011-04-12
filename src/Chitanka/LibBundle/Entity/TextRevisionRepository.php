<?php

namespace Chitanka\LibBundle\Entity;

class TextRevisionRepository extends RevisionRepository
{
	public function getQueryBuilder($orderBys = null)
	{
		$qb = parent::getQueryBuilder($orderBys)
			->select('r', 't', 'a')
			->from($this->getEntityName(), 'r')
			->leftJoin('r.text', 't')
			->leftJoin('t.authors', 'a');

		return $qb;
	}

}
