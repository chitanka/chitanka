<?php namespace App\Entity;

/**
 *
 */
class FeaturedBookRepository extends EntityRepository {
	/**
	 * @param int $limit
	 */
	public function getLatest($limit = null) {
		return $this->_em->createQueryBuilder()
			->from($this->getEntityName(), 'b')
			->select('b')
			->orderBy('b.id', 'desc')
			->getQuery()->setMaxResults($limit)
			->getArrayResult();
	}
}
