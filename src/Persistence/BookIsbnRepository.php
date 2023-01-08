<?php namespace App\Persistence;

use App\Entity\BookIsbn;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 */
class BookIsbnRepository extends EntityRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, BookIsbn::class);
	}

	/**
	 * Retrieve book ids for a given ISBN.
	 * @param string $isbn
	 * @return array
	 */
	public function getBookIdsByIsbn($isbn) {
		return $this->createQueryBuilder('e')
			->select('IDENTITY(e.book)')
			->where('e.code = ?1')->setParameter(1, BookIsbn::normalizeIsbn($isbn))
			->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getResult('id');
	}

}
