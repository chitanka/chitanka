<?php namespace App\Entity;

use App\Util\Date;

/**
 *
 */
class RevisionRepository extends EntityRepository {

	public function getLatest($limit = null, $page = 1, $groupByDate = true) {
		return $this->getByDate(null, $page, $limit, $groupByDate);
	}

	public function getByMonth($month, $page = 1, $limit = null) {
		if (strpos($month, '-') === false) {
			$yearMonth = date('Y') . '-' . $month;
		} else {
			$yearMonth = $month;
		}
		$dates = ["$yearMonth-01", Date::endOfMonth($yearMonth)];

		return $this->getByDate($dates, $page, $limit, false);
	}

	public function getByDate($date, $page = 1, $limit = null, $groupByDate = true) {
		$ids = $this->getIdsByDate($date, $page, $limit);

		if (empty($ids)) {
			return [];
		}

		$revs = $this->getByIds($ids, 'r.date DESC, r.id DESC');

		return $groupByDate ? $this->groupRevisionsByDay($revs) : $revs;
	}

	public function countByDate($date = null) {
		$where = '';
		if (is_array($date)) {
			$where = "WHERE r.date BETWEEN '$date[0]' AND '$date[1]'";
		}
		$dql = sprintf('SELECT COUNT(r.id) FROM %s r %s', $this->getEntityName(), $where);
		$query = $this->_em->createQuery($dql);

		return $query->getSingleScalarResult();
	}

	public function getIdsByDate($date = null, $page = 1, $limit = null) {
		$where = '';
		if (is_array($date)) {
			$where = "WHERE r.date BETWEEN '$date[0]' AND '$date[1]'";
		}
		$dql = sprintf('SELECT r.id FROM %s r %s ORDER BY r.date DESC, r.id DESC', $this->getEntityName(), $where);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);

		return $query->getResult('id');
	}

	/**
	 * @param string $orderBy
	 */
	public function getByIds($ids, $orderBy = null) {
		$texts = $this->getQueryBuilder($orderBy)
			->andWhere(sprintf('r.id IN (%s)', implode(',', $ids)))
			->getQuery()->getArrayResult();

		return WorkSteward::joinPersonKeysForWorks($texts);
	}

	/**
	 * Uses raw SQL and DATE_FORMAT, a MySQL specific function
	 * RAW_SQL
	 */
	public function getMonths() {
		$sql = sprintf('SELECT
				DISTINCT DATE_FORMAT(r.date, "%%Y-%%m") AS month,
				COUNT(*) AS count
			FROM %s r
			WHERE r.date != "0000-00-00"
			GROUP BY month', $this->getClassMetadata()->getTableName());

		return $this->_em->getConnection()->fetchAll($sql);
	}

	/**
	 * RAW_SQL
	 */
	public function getMaxDate() {
		$sql = sprintf('SELECT MAX(r.date) FROM %s r', $this->getClassMetadata()->getTableName());

		return $this->_em->getConnection()->fetchColumn($sql);
	}

	public function getQueryBuilder($orderBys = null) {
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

	public function groupRevisionsByDay($revisions) {
		$grouped = [];
		foreach ($revisions as $revision) {
			$month = $revision['date']->format('Y-m-d');
			$grouped[$month][] = $revision;
		}

		return $grouped;
	}

}
