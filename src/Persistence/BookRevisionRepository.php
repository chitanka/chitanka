<?php namespace App\Persistence;

use App\Entity\BookRevision;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 */
class BookRevisionRepository extends RevisionRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, BookRevision::class);
	}

	public function getQueryBuilder($orderBys = null) {
		$qb = parent::getQueryBuilder($orderBys)
			->select('r', 'b', 'a', 'ap', 's', 'c')
			->from($this->getEntityName(), 'r')
			->leftJoin('r.book', 'b')
			->leftJoin('b.bookAuthors', 'a')
			->leftJoin('a.person', 'ap')
			->leftJoin('b.sequence', 's')
			->leftJoin('b.category', 'c');

		return $qb;
	}

}
