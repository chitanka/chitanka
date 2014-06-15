<?php namespace App\Entity;

/**
 *
 */
class WorkEntryRepository extends EntityRepository {
	/**
	 * @param int $limit
	 */
	public function getLatest($limit = null) {
		return $this->getByIds($this->getLatestIdsByDate($limit), 'e.date DESC');
	}

	/**
	 * @param int $limit
	 */
	public function getLatestIdsByDate($limit = null) {
		$dql = sprintf('SELECT e.id FROM %s e WHERE e.deletedAt IS NULL ORDER BY e.date DESC', $this->getEntityName());
		$query = $this->_em->createQuery($dql)->setMaxResults($limit);

		return $query->getResult('id');
	}

	/**
	 * @param string $title
	 */
	public function getByTitleOrAuthor($title) {
		return $this->getQueryBuilder()
			->where('e.deletedAt IS NULL')
			->andWhere('e.title LIKE ?1 OR e.author LIKE ?1')
			->setParameter(1, "%$title%")
			->getQuery()
			->getArrayResult();
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
			->getResult();
	}

	public function findOlderThan($date) {
		return $this->getQueryBuilder()
			->where('e.deletedAt IS NULL')
			->andWhere('e.date < ?1')
			->setParameter(1, $date)
			->getQuery()
			->getResult();
	}

	/**
	 * @param string $orderBys
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
