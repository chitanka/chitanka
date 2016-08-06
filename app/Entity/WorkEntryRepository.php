<?php namespace App\Entity;

/**
 *
 */
class WorkEntryRepository extends EntityRepository {

	const DEFAULT_CACHE_LIFETIME = 60;

	/**
	 * @param int $limit
	 * @return array
	 */
	public function getLatest($limit = null) {
		return $this->getByIds($this->getLatestIdsByDate($limit), 'e.date DESC');
	}

	/**
	 * @param int $limit
	 * @return array
	 */
	public function getLatestIdsByDate($limit = null) {
		$dql = sprintf('SELECT e.id FROM %s e WHERE e.deletedAt IS NULL ORDER BY e.date DESC', $this->getEntityName());
		$query = $this->_em->createQuery($dql)->setMaxResults($limit);
		$query->useResultCache(true, self::DEFAULT_CACHE_LIFETIME);
		return $query->getResult('id');
	}

	/**
	 * @param string $title
	 * @return WorkEntry[]
	 */
	public function getByTitleOrAuthor($title) {
		return $this->getQueryBuilder()
			->where('e.deletedAt IS NULL')
			->andWhere('e.title LIKE ?1 OR e.author LIKE ?1')
			->setParameter(1, "%$title%")
			->getQuery()
			->useResultCache(true, self::DEFAULT_CACHE_LIFETIME)
			->getResult();
	}

	/**
	 * @param int $limit
	 * @return WorkEntry[]
	 */
	public function findLatest($limit) {
		return $this->getQueryBuilder()
			->where('e.deletedAt IS NULL')
			->orderBy('e.date', 'DESC')
			->setMaxResults($limit)
			->getQuery()
			->useResultCache(true, self::DEFAULT_CACHE_LIFETIME)
			->getResult();
	}

	/**
	 * @param string $date
	 * @return WorkEntry[]
	 */
	public function findOlderThan($date) {
		return $this->getQueryBuilder()
			->where('e.deletedAt IS NULL')
			->andWhere('e.date < ?1')
			->setParameter(1, $date)
			->getQuery()
			->useResultCache(true, self::DEFAULT_CACHE_LIFETIME)
			->getResult();
	}

	/**
	 * @param string $orderBys
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getQueryBuilder($orderBys = null) {
		$qb = parent::getQueryBuilder($orderBys)
			->select('e', 'u', 'c', 'cu')
			->leftJoin('e.user', 'u')
			->leftJoin('e.contribs', 'c')
			->leftJoin('c.user', 'cu')
			->where('e.deletedAt IS NULL');

		return $qb;
	}

}
