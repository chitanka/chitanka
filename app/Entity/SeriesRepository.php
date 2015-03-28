<?php namespace App\Entity;

/**
 *
 */
class SeriesRepository extends EntityRepository {
	protected $queryableFields = ['id', 'slug', 'name', 'orig_name'];

	/**
	 * @param string $slug
	 */
	public function findBySlug($slug) {
		return $this->findOneBy(['slug' => $slug]);
	}

	/**
	 * @param int $limit
	 */
	public function getByPrefix($prefix, $page = 1, $limit = null) {
		$ids = $this->getIdsByPrefix($prefix, $page, $limit);

		return empty($ids) ? [] : $this->getByIds($ids);
	}

	/**
	 * @param string $prefix
	 * @param int $page
	 * @param int $limit
	 */
	public function getIdsByPrefix($prefix, $page, $limit) {
		$where = $prefix ? "s.name LIKE '$prefix%'" : "s.name != ''";
		$dql = sprintf('SELECT s.id FROM %s s WHERE %s ORDER BY s.name', $this->getEntityName(), $where);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);

		return $query->getResult('id');
	}

	public function getByIds($ids, $orderBy = null) {
		$texts = $this->getQueryBuilder()
			->where(sprintf('e.id IN (%s)', implode(',', $ids)))
			->getQuery()->getArrayResult();

		return $texts;
	}

	public function countByPrefix($prefix) {
		$where = $prefix ? "s.name LIKE '$prefix%'" : "s.name != ''";
		$dql = sprintf('SELECT COUNT(s.id) FROM %s s WHERE %s', $this->getEntityName(), $where);
		$query = $this->_em->createQuery($dql);

		return $query->getSingleScalarResult();
	}

	/**
	 * @param string $name
	 * @param int $limit
	 */
	public function getByNames($name, $limit = null) {
		$q = $this->getQueryBuilder()
			->where('e.name LIKE ?1 OR e.orig_name LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($name))
			->getQuery();
		if ($limit) {
			$q->setMaxResults($limit);
		}
		return $q->getArrayResult();
	}

	/**
	 * @param string $orderBys
	 */
	public function getQueryBuilder($orderBys = null) {
		return $this->_em->createQueryBuilder()
			->select('e', 'a')
			->from($this->getEntityName(), 'e')
			->leftJoin('e.authors', 'a')
			->addOrderBy('e.name');
	}

}
