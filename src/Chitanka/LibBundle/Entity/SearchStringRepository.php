<?php

namespace Chitanka\LibBundle\Entity;

class SearchStringRepository extends EntityRepository
{
	public function getLatest($limit = null)
	{
		return $this->getByIds($this->getLatestIds($limit), 'e.count DESC, e.date DESC');
	}


	public function getLatestIds($limit = null)
	{
		$dql = sprintf('SELECT e.id FROM %s e ORDER BY e.count DESC, e.date DESC', $this->getEntityName());
		$query = $this->_em->createQuery($dql)->setMaxResults($limit);

		return $query->getResult('id');
	}

}
