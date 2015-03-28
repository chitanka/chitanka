<?php namespace App\Entity;

/**
 *
 */
class SequenceRepository extends EntityRepository {
	protected $queryableFields = ['id', 'slug', 'name', 'publisher'];

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
		$where = $prefix ? "WHERE s.name LIKE '$prefix%'" : '';
		$dql = sprintf('SELECT s FROM %s s %s ORDER BY s.name', $this->getEntityName(), $where);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);

		return $query->getArrayResult();
	}

	public function countByPrefix($prefix) {
		$where = $prefix ? "WHERE s.name LIKE '$prefix%'" : '';
		$dql = sprintf('SELECT COUNT(s.id) FROM %s s %s', $this->getEntityName(), $where);
		$query = $this->_em->createQuery($dql);

		return $query->getSingleScalarResult();
	}

	/**
	 * @param int $limit
	 * @param string $name
	 */
	public function getByNames($name, $limit = null) {
		$q = $this->getQueryBuilder()
			->where('e.name LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($name))
			->getQuery();
		if ($limit) {
			$q->setMaxResults($limit);
		}
		return $q->getArrayResult();
	}

}
