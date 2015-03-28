<?php namespace App\Entity;

/**
 * A repository for books
 */
class BookRepository extends EntityRepository {

	protected $queryableFields = ['id', 'title', 'subtitle', 'origTitle'];

	/**
	 * Fetch a book with all important relations
	 * @param int $id
	 * @return Book
	 */
	public function get($id) {
		return $this->_em->createQueryBuilder()
			->select('b', 'a', 'ap', 's', 'c', 'l', 'ls', 't', 'ta', 'tap')
			->from($this->getEntityName(), 'b')
			->leftJoin('b.bookAuthors', 'a')
			->leftJoin('a.person', 'ap')
			->leftJoin('b.sequence', 's')
			->leftJoin('b.category', 'c')
			->leftJoin('b.links', 'l')
			->leftJoin('l.site', 'ls')
			->leftJoin('b.texts', 't')
			->leftJoin('t.textAuthors', 'ta')
			->leftJoin('ta.person', 'tap')
			->where('b.id = ?1')->setParameter(1, $id)
			->getQuery()->getSingleResult();
	}

	/**
	 * @param Category $category
	 * @param int $page
	 * @param int $limit
	 */
	public function getByCategory($category, $page = 1, $limit = null) {
		$ids = $this->getIdsByCategory($category, $page, $limit);

		return empty($ids) ? [] : $this->getByIds($ids);
	}

	/**
	 * Retrieve books by ISBN.
	 * There may be multiple books for a given ISBN.
	 * @param string $isbn
	 * @return array
	 */
	public function getByIsbn($isbn) {
		$ids = $this->getEntityManager()->getRepository('App:BookIsbn')->getBookIdsByIsbn($isbn);
		return empty($ids) ? [] : $this->getByIds($ids);
	}

	/**
	 * @param Category $category
	 * @param int $page
	 * @param int $limit
	 */
	private function getIdsByCategory($category, $page = 1, $limit = null) {
		$dql = "SELECT b.id FROM {$this->getEntityName()} b WHERE b.category = {$category->getId()} ORDER BY b.title";
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);

		return $query->getResult('id');
	}

	/**
	 * @param Sequence $sequence
	 * @param int $page
	 * @param int $limit
	 */
	public function getBySequence($sequence, $page = 1, $limit = null) {
		$ids = $this->getIdsBySequence($sequence, $page, $limit);

		return empty($ids) ? [] : $this->getByIds($ids, 'e.seqnr, e.title');
	}

	/**
	 * @param Sequence $sequence
	 * @param int $page
	 * @param int $limit
	 */
	private function getIdsBySequence($sequence, $page = 1, $limit = null) {
		$dql = "SELECT b.id FROM {$this->getEntityName()} b WHERE b.sequence = {$sequence->getId()} ORDER BY b.seqnr, b.title";
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);

		return $query->getResult('id');
	}

	/**
	 * @param string $prefix
	 * @param int $page
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
	private function getIdsByPrefix($prefix, $page, $limit) {
		$where = $prefix ? "b.title LIKE '$prefix%'" : '1=1';
		$dql = "SELECT b.id FROM {$this->getEntityName()} b WHERE $where ORDER BY b.title";
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);

		return $query->getResult('id');
	}

	/**
	 * @param array $ids
	 * @param string $orderBy
	 */
	public function getByIds($ids, $orderBy = null) {
		return WorkSteward::joinPersonKeysForBooks(parent::getByIds($ids, $orderBy));
	}

	/**
	 * @param string $prefix
	 * @return int
	 */
	public function countByPrefix($prefix) {
		$where = $prefix ? "b.title LIKE '$prefix%'" : '1=1';
		$dql = "SELECT COUNT(b.id) FROM {$this->getEntityName()} b WHERE $where";
		$query = $this->_em->createQuery($dql);

		return $query->getSingleScalarResult();
	}

	/**
	 * @param string $title
	 * @param int $limit
	 * @return array
	 */
	public function getByTitles($title, $limit = null) {
		$q = $this->getQueryBuilder()
			->where('e.title LIKE ?1 OR e.subtitle LIKE ?1 OR e.origTitle LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($title))
			->setMaxResults($limit)
			->getQuery();
		return WorkSteward::joinPersonKeysForBooks($q->getArrayResult());
	}

	/**
	 * @param string $titleOrIsbn
	 * @param int $limit
	 * @return array
	 */
	public function getByTitleOrIsbn($titleOrIsbn, $limit = null) {
		$isbn = BookIsbn::normalizeIsbn($titleOrIsbn);
		if (empty($isbn)) {
			return $this->getByTitles($titleOrIsbn, $limit);
		}
		$q = $this->getQueryBuilder()
			->leftJoin('e.isbns', 'isbn')
			->where('e.title LIKE ?1 OR e.subtitle LIKE ?1 OR e.origTitle LIKE ?1 OR isbn.code = ?2')
			->setParameters([1 => $this->stringForLikeClause($titleOrIsbn), 2 => $isbn])
			->setMaxResults($limit)
			->getQuery();
		return WorkSteward::joinPersonKeysForBooks($q->getArrayResult());
	}

	/**
	 * @param Person $author
	 * @return array
	 */
	public function getByAuthor($author) {
		$books = $this->getQueryBuilder('s.name, e.seqnr, e.title')
			->andWhere('ap.id = ?1')->setParameter(1, $author->getId())
			->getQuery()
			->getArrayResult();
		return WorkSteward::joinPersonKeysForBooks($books);
	}

	/**
	 * @param array $params
	 * @return array
	 */
	public function getByQuery($params) {
		return WorkSteward::joinPersonKeysForBooks(parent::getByQuery($params));
	}

	/**
	 * @param string $orderBys
	 */
	public function getQueryBuilder($orderBys = null) {
		if (is_null($orderBys)) {
			$orderBys = 'e.title';
		}
		$qb = parent::getQueryBuilder($orderBys)
			->addSelect('a', 'ap', 's', 'c')
			->leftJoin('e.bookAuthors', 'a')
			->leftJoin('a.person', 'ap')
			->leftJoin('e.sequence', 's')
			->leftJoin('e.category', 'c');

		return $qb;
	}

	/**
	 * @param int $page
	 * @param int $limit
	 * @return array
	 */
	public function getWithMissingCover($page = 1, $limit = null) {
		$ids = $this->getIdsWithMissingCover($page, $limit);
		return empty($ids) ? [] : $this->getByIds($ids);
	}

	/**
	 * @param int $page
	 * @param int $limit
	 * @return array
	 */
	private function getIdsWithMissingCover($page = 1, $limit = null) {
		$dql = "SELECT b.id FROM {$this->getEntityName()} b WHERE b.hasCover = 0 ORDER BY b.title ASC";
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		return $query->getResult('id');
	}

	/**
	 * @return int
	 */
	public function getCountWithMissingCover() {
		$dql = "SELECT COUNT(b.id) FROM {$this->getEntityName()} b WHERE b.hasCover = 0";
		$query = $this->_em->createQuery($dql);

		return $query->getSingleScalarResult();
	}
}
