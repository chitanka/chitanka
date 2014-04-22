<?php

namespace App\Entity;

use Doctrine\ORM\EntityRepository as DoctrineEntityRepository;

abstract class EntityRepository extends DoctrineEntityRepository
{
	protected $queryableFields = array();


	public function persist($object)
	{
		$em = $this->getEntityManager();
		$em->persist($object);
		$em->flush();
	}

	public function flush()
	{
		$this->getEntityManager()->flush();
	}

	public function count($where = null) {
		return $this->getCount($where);
	}

	public function getCount($where = null) {
		$qb = $this->createQueryBuilder('e')->select('COUNT(e.id)');
		if ($where) {
			$qb->andWhere($where);
		}
		return $qb->getQuery()->getSingleScalarResult();
	}

	protected function setPagination($query, $page, $limit)
	{
		if ($limit) {
			$query->setMaxResults($limit)->setFirstResult(($page - 1) * $limit);
		}

		return $query;
	}

	public function getRandom($where = null) {
		return $this->getRandomQuery($where)->getSingleResult();
	}

	public function getRandomId($where = null) {
		return $this->getRandomQuery($where, 'e.id')->getSingleScalarResult();
	}

	protected function getRandomQuery($where = null, $select = null) {
		$qb = $this->getEntityManager()->createQueryBuilder()
			->select($select ?: 'e')
			->from($this->getEntityName(), 'e');
		if ($where) {
			$qb->andWhere($where);
		}
		$query = $qb->getQuery()
			->setMaxResults(1)
			->setFirstResult(rand(1, $this->getCount($where)) - 1);

		return $query;
	}

	public function findByIds(array $ids)
	{
		if (empty($ids)) {
			return array();
		}
		return $this->getQueryBuilder()
			->where(sprintf('e.id IN (%s)', implode(',', $ids)))
			->getQuery()->getResult();
	}

	public function getByIds($ids, $orderBy = null)
	{
		if (empty($ids)) {
			return array();
		}

		return $this->getQueryBuilder($orderBy)
			->where(sprintf('e.id IN (%s)', implode(',', $ids)))
			->getQuery()->getArrayResult();
	}


	public function getByQuery($params)
	{
		if (empty($params['text']) || empty($params['by'])) {
			return array();
		}

		switch ($params['match']) {
			case 'exact':
				$op = '=';
				$param = $params['text'];
				break;
			case 'prefix':
				$op = 'LIKE';
				$param = "$params[text]%";
				break;
			case 'suffix':
				$op = 'LIKE';
				$param = "%$params[text]";
				break;
			default:
				$op = 'LIKE';
				$param = "%$params[text]%";
				break;
		}
		$tests = array();
		foreach (explode(',', $params['by']) as $field) {
			if (in_array($field, $this->queryableFields)) {
				$tests[] = "e.$field $op ?1";
			}
		}
		if (empty($tests)) {
			return array();
		}

		$query = $this->getQueryBuilder()
			->where(implode(' OR ', $tests))->setParameter(1, $param)
			->getQuery();
		if (isset($params['limit'])) {
			$query->setMaxResults($params['limit']);
		}

		return $query->getArrayResult();
	}


	public function getQueryableFields()
	{
		return $this->queryableFields;
	}


	public function getQueryBuilder($orderBys = null)
	{
		$qb = $this->createQueryBuilder('e');

		if ($orderBys) {
			foreach (explode(',', $orderBys) as $orderBy) {
				$orderBy = ltrim($orderBy);
				if (strpos($orderBy, ' ') === false) {
					$field = $orderBy;
					$order = 'asc';
				} else {
					list($field, $order) = explode(' ', ltrim($orderBy));
				}
				$qb->addOrderBy($field, $order);
			}
		}

		return $qb;
	}

	protected function stringForLikeClause($s)
	{
		return "%".str_replace(' ', '% ', $s)."%";
	}
}
