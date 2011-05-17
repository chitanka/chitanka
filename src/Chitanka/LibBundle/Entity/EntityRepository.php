<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\EntityRepository as DoctrineEntityRepository;

abstract class EntityRepository extends DoctrineEntityRepository
{
	protected $queryableFields = array();


	public function flush()
	{
		$this->_em->flush();
	}

	public function getCount($where = null)
	{
		$qb = $this->_em->createQueryBuilder()
			->select('COUNT(e.id)')
			->from($this->getEntityName(), 'e');
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


	public function getRandom()
	{
		$dql = sprintf('SELECT e FROM %s e WHERE e.id <= %d ORDER BY e.id DESC', $this->getEntityName(), rand(1, $this->getCount()));
		$query = $this->_em->createQuery($dql);
		$query->setMaxResults(1);

		return $query->getSingleResult();
	}


	public function getRandomId($where = '')
	{
		if ($where) {
			$where = 'AND '.$where;
		}
		$dql = sprintf('SELECT e.id FROM %s e WHERE e.id <= %d %s ORDER BY e.id DESC', $this->getEntityName(), rand(1, $this->getCount()), $where);
		$query = $this->_em->createQuery($dql);
		$query->setMaxResults(1);

		return $query->getSingleScalarResult();
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


	public function getByQuery($query)
	{
		if (empty($query['text']) || empty($query['by'])) {
			return false;
		}

		switch ($query['match']) {
			case 'exact':
				$op = '=';
				$param = $query['text'];
				break;
			case 'prefix':
				$op = 'LIKE';
				$param = "$query[text]%";
				break;
			case 'suffix':
				$op = 'LIKE';
				$param = "%$query[text]";
				break;
			default:
				$op = 'LIKE';
				$param = "%$query[text]%";
				break;
		}
		$tests = array();
		foreach (explode('|', $query['by']) as $field) {
			if (in_array($field, $this->queryableFields)) {
				$tests[] = "e.$field $op ?1";
			}
		}
		if (empty($tests)) {
			return false;
		}

		return $this->getQueryBuilder()
			->where(implode(' OR ', $tests))->setParameter(1, $param)
			->getQuery()->getArrayResult();
	}


	public function getQueryableFields()
	{
		return $this->queryableFields;
	}


	public function getQueryBuilder($orderBys = null)
	{
		$qb = $this->_em->createQueryBuilder()
			->select('e')
			->from($this->getEntityName(), 'e');

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
}
