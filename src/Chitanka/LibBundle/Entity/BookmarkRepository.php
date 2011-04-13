<?php

namespace Chitanka\LibBundle\Entity;

class BookmarkRepository extends EntityRepository
{
	public function getLatestByUser($user, $limit = null)
	{
		return $this->getByUser($user, 1, $limit, 'e.created_at DESC');
	}


	public function getByUser($user, $page = 1, $limit = null, $orderBys = 't.title ASC')
	{
		$ids = $this->getIdsByUser($user, $page, $limit, $orderBys);

		return empty($ids) ? array() : $this->getByIds($ids, $orderBys);
	}

	public function getIdsByUser($user, $page, $limit, $orderBys = 't.title ASC')
	{
		$dql = sprintf('SELECT e.id FROM %s e LEFT JOIN e.text t WHERE e.user = %d ORDER BY %s', $this->getEntityName(), $user->getId(), $orderBys);
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
		return parent::getQueryBuilder($orderBys)
			->addSelect('t', 's', 'a')
			->leftJoin('e.text', 't')
			->leftJoin('t.series', 's')
			->leftJoin('t.authors', 'a');
	}

}
