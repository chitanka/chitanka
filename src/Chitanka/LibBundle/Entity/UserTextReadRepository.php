<?php

namespace Chitanka\LibBundle\Entity;

class UserTextReadRepository extends EntityRepository
{

	public function getLatestByUser($user, $limit = null)
	{
		return $this->getByUser($user, 1, $limit);
	}


	public function getByUser($user, $page = 1, $limit = null)
	{
		$ids = $this->getIdsByUser($user, $page, $limit);

		return empty($ids) ? array() : $this->getByIds($ids);
	}

	public function getIdsByUser($user, $page, $limit)
	{
		$dql = sprintf('SELECT e.id FROM %s e LEFT JOIN e.text t WHERE e.user = %d ORDER BY t.title ASC', $this->getEntityName(), $user->getId());
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$ids = $query->getResult('id');

		return $ids;
	}


	public function countByUser($user)
	{
		return $this->getCount('e.user = '.$user->getId());
	}


	public function getQueryBuilder($orderBys = null)
	{
		return parent::getQueryBuilder('t.title ASC')
			->addSelect('t', 's', 'a')
			->leftJoin('e.text', 't')
			->leftJoin('t.series', 's')
			->leftJoin('t.authors', 'a');
	}

}
