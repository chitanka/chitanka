<?php

namespace Chitanka\LibBundle\Entity;

class TextCommentRepository extends EntityRepository
{
	public function getLatest($limit = null)
	{
		return $this->getByIds($this->getLatestIdsByDate($limit), 'e.time DESC');
	}


	public function getLatestIdsByDate($limit = null)
	{
		$dql = sprintf('SELECT e.id FROM %s e WHERE e.is_shown = 1 ORDER BY e.time DESC', $this->getEntityName());
		$query = $this->_em->createQuery($dql)->setMaxResults($limit);

		return $query->getResult('id');
	}


	public function getByText($text)
	{
		return $this->getQueryBuilder()
			->where('e.text = ?1')->setParameter(1, $text->getId())
			->orderBy('e.time', 'ASC')
			->getQuery()->getArrayResult();
	}


	public function getQueryBuilder($orderBys = null)
	{
		$qb = parent::getQueryBuilder($orderBys)
			->select('e', 't', 'a', 's', 'u')
			->leftJoin('e.text', 't')
			->leftJoin('t.series', 's')
			->leftJoin('t.authors', 'a')
			->leftJoin('e.user', 'u');

		return $qb;
	}

}
