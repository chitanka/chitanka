<?php

namespace Chitanka\LibBundle\Entity;

class RevisionRepository extends EntityRepository
{

	public function getLatest($limit = null, $groupByDate = true)
	{
		return $this->getByDate(null, 1, $limit, $groupByDate);
	}


	public function getByDate($month, $page = 1, $limit = null, $groupByDate = true)
	{
		$ids = $this->getIdsByDate($month, $page, $limit);

		if (empty($ids)) {
			return array();
		}

		$revs = $this->getByIds($ids, 'r.date DESC, r.id DESC');

		return $groupByDate ? $this->groupRevisionsByDay($revs) : $revs;
	}

	public function countByDate($date = null)
	{
		$where = '';
		if (is_array($date)) {
			$where = "WHERE r.date BETWEEN '$date[0]' AND '$date[1]'";
		}
		$dql = sprintf('SELECT COUNT(r.id) FROM %s r %s', $this->getEntityName(), $where);
		$query = $this->_em->createQuery($dql);

		return $query->getSingleScalarResult();
	}

	public function getIdsByDate($date = null, $page = 1, $limit = null)
	{
		$where = '';
		if (is_array($date)) {
			$where = "WHERE r.date BETWEEN '$date[0]' AND '$date[1]'";
		}
		$dql = sprintf('SELECT r.id FROM %s r %s ORDER BY r.date DESC, r.id DESC', $this->getEntityName(), $where);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);

		return $query->getResult('id');
	}


	public function getByIds($ids, $orderBy = null)
	{
		$texts = $this->getQueryBuilder($orderBy)
			->where(sprintf('r.id IN (%s)', implode(',', $ids)))
			->getQuery()->getArrayResult();

		return $texts;
	}


	/**
	* Uses raw SQL and DATE_FORMAT, a MySQL specific function
	* @RawSql
	*/
	public function getMonths()
	{
		$sql = sprintf('SELECT
				DISTINCT DATE_FORMAT(r.date, "%%Y-%%m") AS month,
				COUNT(*) AS count
			FROM %s r
			WHERE r.date != "0000-00-00"
			GROUP BY month', $this->getClassMetadata()->getTableName());

		return $this->_em->getConnection()->fetchAll($sql);
	}


	public function getQueryBuilder($orderBys = null)
	{
		$qb = $this->_em->createQueryBuilder();

		if ($orderBys) {
			foreach (explode(',', $orderBys) as $orderBy) {
				list($field, $order) = explode(' ', ltrim($orderBy));
				$qb->addOrderBy($field, $order);
			}
		} else {
			$qb->orderBy('r.date', 'DESC');
		}

		return $qb;
	}


	public function groupRevisionsByDay($revisions)
	{
		$grouped = array();
		foreach ($revisions as $revision) {
			$month = $revision['date']->format('Y-m-d');
			$grouped[$month][] = $revision;
		}

		return $grouped;
	}

}
