<?php namespace App\Entity;

/**
 *
 */
class ForeignBookRepository extends EntityRepository {
	/**
	 * @param int $limit
	 */
	public function getLatest($limit = null) {
		return $this->createQueryBuilder('b')
			->orderBy('b.isFree', 'desc')
			->addOrderBy('b.id', 'desc')
			->getQuery()->setMaxResults($limit)
			->getArrayResult();
	}
}
