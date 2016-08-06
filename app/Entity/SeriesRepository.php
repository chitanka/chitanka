<?php namespace App\Entity;

/**
 *
 */
class SeriesRepository extends EntityRepository {
	protected $queryableFields = ['id', 'slug', 'name', 'origName'];

	/**
	 * @param string $slug
	 * @return Series
	 */
	public function findBySlug($slug) {
		return $this->findOneBy(['slug' => $slug]);
	}

	/**
	 * @param string $prefix
	 * @param int $page
	 * @param int $limit
	 * @return array
	 */
	public function getByPrefix($prefix, $page = 1, $limit = null) {
		$ids = $this->getIdsByPrefix($prefix, $page, $limit);
		return empty($ids) ? [] : $this->getByIds($ids);
	}

	/**
	 * @param string $prefix
	 * @param int $page
	 * @param int $limit
	 * @return array
	 */
	public function getIdsByPrefix($prefix, $page, $limit) {
		$where = $prefix ? "s.name LIKE '$prefix%'" : "s.name != ''";
		$dql = sprintf('SELECT s.id FROM %s s WHERE %s ORDER BY s.name', $this->getEntityName(), $where);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$query->useResultCache(true, self::DEFAULT_CACHE_LIFETIME);
		return $query->getResult('id');
	}

	/**
	 * @param array $ids
	 * @param string|null $orderBy
	 * @return array
	 */
	public function getByIds($ids, $orderBy = null) {
		return $this->getQueryBuilder()
			->where(sprintf('e.id IN (%s)', implode(',', $ids)))
			->getQuery()
			->useResultCache(true, self::DEFAULT_CACHE_LIFETIME)
			->getArrayResult();
	}

	/**
	 * @param string $prefix
	 * @return int
	 */
	public function countByPrefix($prefix) {
		$where = $prefix ? "s.name LIKE '$prefix%'" : "s.name != ''";
		$dql = sprintf('SELECT COUNT(s.id) FROM %s s WHERE %s', $this->getEntityName(), $where);
		$query = $this->_em->createQuery($dql);
		$query->useResultCache(true, self::DEFAULT_CACHE_LIFETIME);
		return $query->getSingleScalarResult();
	}

	/**
	 * @param string $name
	 * @param int $limit
	 * @return array
	 */
	public function getByNames($name, $limit = null) {
		$query = $this->getQueryBuilder()
			->where('e.name LIKE ?1 OR e.origName LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($name))
			->getQuery();
		$query->useResultCache(true, self::DEFAULT_CACHE_LIFETIME);
		$this->addLimitingToQuery($query, $limit);
		return $query->getArrayResult();
	}

	/**
	 * @param string $orderBys
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getQueryBuilder($orderBys = null) {
		return $this->_em->createQueryBuilder()
			->select('e', 'a')
			->from($this->getEntityName(), 'e')
			->leftJoin('e.authors', 'a')
			->addOrderBy('e.name');
	}

}
