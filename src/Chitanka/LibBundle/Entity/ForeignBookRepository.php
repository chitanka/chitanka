<?php

namespace Chitanka\LibBundle\Entity;

class ForeignBookRepository extends EntityRepository
{
	public function getLatest($limit = null)
	{
		return $this->_em->createQueryBuilder()
			->from($this->getEntityName(), 'b')
			->select('b')
			->orderBy('b.id', 'desc')
			->getQuery()->setMaxResults($limit)
			->getArrayResult();
	}
}
