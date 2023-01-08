<?php namespace App\Persistence;

use App\Util\Date;

/**
 *
 */
abstract class RevisionRepository extends EntityRepository {

	public function getLatest($limit = null, $page = 1, $groupByDate = true) {
		return $this->getByDate(null, $page, $limit, $groupByDate);
	}

	public function getByMonth(\DateTime $date, $page = 1, $limit = null) {
		$dates = [$date->format('Y-m-01'), Date::endOfMonth($date->format('Y-m'))];
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
		$where = $date ? 'WHERE r.date '.$this->createWhereClauseForDate($date) : '';
		$dql = sprintf('SELECT COUNT(r.id) FROM %s r %s', $this->getEntityName(), $where);
		$query = $this->_em->createQuery($dql);
		$query->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);
		return $query->getSingleScalarResult();
	}

	public function getIdsByDate($date = null, $page = 1, $limit = null) {
		$where = $date ? 'WHERE r.date '.$this->createWhereClauseForDate($date) : '';
		$dql = sprintf('SELECT r.id FROM %s r %s ORDER BY r.date DESC, r.id DESC', $this->getEntityName(), $where);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$query->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);
		return $query->getResult('id');
	}

	private function createWhereClauseForDate($date): string {
		if (empty($date)) {
			return '';
		}
		if (is_string($date)) {
			$date = ["$date 00:00:00", "$date 23:59:59"];
		}
		if (is_array($date) && count($date) > 1) {
			return sprintf("BETWEEN '%s' AND '%s'", $this->dateToString($date[0]), $this->dateToString($date[1]));
		}
		return '';
	}

	/**
	 * @param array $ids
	 * @param string $orderBy
	 * @return BookRevision[]|TextRevision[]
	 */
	public function getByIds($ids, $orderBy = null) {
		$texts = $this->getQueryBuilder($orderBy)
			->andWhere(sprintf('r.id IN (%s)', implode(',', $ids)))
			->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getResult();
		return $texts;
	}

	/**
	 * Uses raw SQL and DATE_FORMAT, a MySQL specific function
	 * RAW_SQL
	 */
	public function getMonths() {
		$table = $this->getClassMetadata()->getTableName();
		return $this->fetchFromCache('Months_'.$table, function() use ($table) {
			$sql = sprintf('SELECT DISTINCT %s AS month, COUNT(*) AS count
				FROM %s r
				WHERE r.date != "0000-00-00"
				GROUP BY month', $this->getDateFormatDbFunction('r.date'), $table);
			return $this->_em->getConnection()->fetchAll($sql);
		});
	}

	protected function getDateFormatDbFunction(string $dbField): string {
		switch ($this->getPlatform()) {
			case self::PLATFORM_MYSQL:  return "date_format($dbField, \"%Y-%m\")";
			case self::PLATFORM_SQLITE: return "strftime(\"%Y-%m\", $dbField)";
			default: return $dbField;
		}
	}

	/**
	 * RAW_SQL
	 */
	public function getMaxDate() {
		$table = $this->getClassMetadata()->getTableName();
		return $this->fetchFromCache('MaxDate_'.$table, function() use ($table) {
			$sql = sprintf('SELECT MAX(r.date) FROM %s r', $table);
			return $this->_em->getConnection()->fetchColumn($sql);
		});
	}

	public function getQueryBuilder($orderBys = null) {
		$qb = $this->_em->createQueryBuilder();

		if ($orderBys) {
			foreach (explode(',', $orderBys) as $orderBy) {
				[$field, $order] = explode(' ', ltrim($orderBy));
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
			$month = $revision->getDate()->format('Y-m-d');
			$grouped[$month][] = $revision;
		}

		return $grouped;
	}

}
