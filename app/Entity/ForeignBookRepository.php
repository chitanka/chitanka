<?php namespace App\Entity;

/**
 *
 */
class ForeignBookRepository extends EntityRepository {
	/**
	 * @param int $limit
	 * @return ForeignBook[]
	 */
	public function getLatest($limit = null) {
		return $this->createQueryBuilder('b')
			->where('b.isActive = 1')
			->orderBy('b.publishedAt', 'desc')
			->getQuery()->setMaxResults($limit)
			->getResult();
	}
}
