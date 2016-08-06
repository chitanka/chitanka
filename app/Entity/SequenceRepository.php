<?php namespace App\Entity;

/**
 *
 */
class SequenceRepository extends EntityRepository {
	protected $queryableFields = ['id', 'slug', 'name', 'publisher'];

	/**
	 * @param string $slug
	 * @return Sequence
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
		$where = $prefix ? "WHERE s.name LIKE '$prefix%'" : '';
		$dql = sprintf('SELECT s FROM %s s %s ORDER BY s.name', $this->getEntityName(), $where);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$query->useResultCache(true, self::DEFAULT_CACHE_LIFETIME);
		return $query->getArrayResult();
	}

	/**
	 * @param string $prefix
	 * @return int
	 */
	public function countByPrefix($prefix) {
		$where = $prefix ? "WHERE s.name LIKE '$prefix%'" : '';
		$dql = sprintf('SELECT COUNT(s.id) FROM %s s %s', $this->getEntityName(), $where);
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
			->where('e.name LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($name))
			->getQuery();
		$query->useResultCache(true, self::DEFAULT_CACHE_LIFETIME);
		$this->addLimitingToQuery($query, $limit);
		return $query->getArrayResult();
	}

}
