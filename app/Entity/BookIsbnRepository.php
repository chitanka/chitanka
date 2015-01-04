<?php namespace App\Entity;

/**
 *
 */
class BookIsbnRepository extends EntityRepository {

	/**
	 * Retrieve book ids for a given ISBN.
	 * @param string $isbn
	 * @return array
	 */
	public function getBookIdsByIsbn($isbn) {
		return $this->createQueryBuilder('e')
			->select('IDENTITY(e.book)')
			->where('e.code = ?1')->setParameter(1, BookIsbn::normalizeIsbn($isbn))
			->getQuery()->getResult('id');
	}

}
