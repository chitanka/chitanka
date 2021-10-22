<?php namespace App\Entity;

use App\Entity\Query\SortingDefinition;

/**
 * A repository for books
 */
class BookRepository extends EntityRepository {

	protected $queryableFields = ['id', 'title', 'subtitle', 'origTitle'];
	protected $sortableFields = [
		'title',
		'createdAt',
		'year',
	];
	protected $defaultSortingField = 'title';

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
			->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getSingleResult();
	}

	/**
	 * @param Category|array $category
	 * @param int $page
	 * @param int $limit
	 * @return Book[]
	 */
	public function findByCategory($category, $page = 1, $limit = null, SortingDefinition $sorting = null) {
		$ids = $this->getIdsByCategory($category, $page, $limit, $sorting);
		return empty($ids) ? [] : $this->findByIds($ids, $sorting);
	}

	/**
	 * Retrieve books by ISBN.
	 * There may be multiple books for a given ISBN.
	 * @param string $isbn
	 * @return Book[]
	 */
	public function findByIsbn($isbn) {
		$ids = $this->getEntityManager()->getRepository('App:BookIsbn')->getBookIdsByIsbn($isbn);
		return empty($ids) ? [] : $this->findByIds($ids);
	}

	/**
	 * @param Category|array $category
	 * @param int $page
	 * @param int $limit
	 */
	private function getIdsByCategory($category, $page = 1, $limit = null, SortingDefinition $sorting = null) {
		if ($category instanceof Category) {
			$ids = [$category->getId()];
		} else {
			$ids = implode(',', $category);
		}
		$dql = sprintf("SELECT e.id FROM {$this->getEntityName()} e WHERE e.category IN (%s) ORDER BY $sorting", $ids);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$query->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);
		return $query->getResult('id');
	}

	/**
	 * @param Sequence $sequence
	 * @param int $page
	 * @param int $limit
	 * @return Book[]
	 */
	public function findBySequence($sequence, $page = 1, $limit = null) {
		$ids = $this->getIdsBySequence($sequence, $page, $limit);
		return empty($ids) ? [] : $this->findByIds($ids, 'e.seqnr, e.title');
	}

	/**
	 * @param Sequence $sequence
	 * @param int $page
	 * @param int $limit
	 */
	private function getIdsBySequence($sequence, $page = 1, $limit = null) {
		$dql = "SELECT b.id FROM {$this->getEntityName()} b WHERE b.sequence = {$sequence->getId()} ORDER BY b.seqnr, b.title";
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$query->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);
		return $query->getResult('id');
	}

	/**
	 * @param string $prefix
	 * @param int $page
	 * @param int $limit
	 */
	public function findByPrefix($prefix, $page = 1, $limit = null, SortingDefinition $sorting = null) {
		$ids = $this->getIdsByPrefix($prefix, $page, $limit, $sorting);

		return empty($ids) ? [] : $this->findByIds($ids, $sorting);
	}

	/**
	 * @param string $prefix
	 * @param int $page
	 * @param int $limit
	 */
	private function getIdsByPrefix($prefix, $page, $limit, SortingDefinition $sorting = null) {
		$where = $prefix ? "e.title LIKE '$prefix%'" : '1=1';
		$dql = "SELECT e.id FROM {$this->getEntityName()} e WHERE $where ORDER BY $sorting";
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$query->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);
		return $query->getResult('id');
	}

	/**
	 * @param string $prefix
	 * @return int
	 */
	public function countByPrefix($prefix) {
		$where = $prefix ? "b.title LIKE '$prefix%'" : '1=1';
		$dql = "SELECT COUNT(b.id) FROM {$this->getEntityName()} b WHERE $where";
		$query = $this->_em->createQuery($dql);
		$query->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);
		return $query->getSingleScalarResult();
	}

	/**
	 * @param string $title
	 * @param int $limit
	 * @return Book[]
	 */
	public function findByTitles($title, $limit = null) {
		$books = $this->getQueryBuilder()
			->where('e.title LIKE ?1 OR e.subtitle LIKE ?1 OR e.origTitle LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($title))
			->setMaxResults($limit)
			->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getResult();
		return $books;
	}

	/**
	 * @param string $titleOrIsbn
	 * @param int $limit
	 * @return Book[]
	 */
	public function findByTitleOrIsbn($titleOrIsbn, $limit = null) {
		$isbn = BookIsbn::normalizeIsbn($titleOrIsbn);
		if (empty($isbn)) {
			return $this->findByTitles($titleOrIsbn, $limit);
		}
		$books = $this->getQueryBuilder()
			->leftJoin('e.isbns', 'isbn')
			->where('e.title LIKE ?1 OR e.subtitle LIKE ?1 OR e.origTitle LIKE ?1 OR isbn.code = ?2')
			->setParameters([1 => $this->stringForLikeClause($titleOrIsbn), 2 => $isbn])
			->setMaxResults($limit)
			->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getResult();
		return $books;
	}

	/**
	 * @param Person $author
	 * @return Book[]
	 */
	public function findByAuthor($author) {
		$books = $this->getQueryBuilder('s.name, e.seqnr, e.title')
			->andWhere('ap.id = ?1')->setParameter(1, $author->getId())
			->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getResult();
		return $books;
	}

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

	/** @return Book[] */
	public function findWithMissingCover(int $page = 1, int $limit = null, SortingDefinition $sorting = null) {
		return $this->findByIds($this->getIdsWithMissingCover($page, $limit, $sorting), $sorting);
	}
	private function getIdsWithMissingCover(int $page = 1, int $limit = null, SortingDefinition $sorting = null): array {
		return $this->getIdsForDql("SELECT e.id FROM {$this->getEntityName()} e WHERE e.hasCover = 0 ORDER BY $sorting", $page, $limit);
	}
	public function getCountWithMissingCover(): int {
		return $this->getCountForDql("SELECT COUNT(b.id) FROM {$this->getEntityName()} b WHERE b.hasCover = 0");
	}

	/** @return Book[] */
	public function findWithMissingBibliomanId(int $page = 1, int $limit = null, SortingDefinition $sorting = null) {
		return $this->findByIds($this->getIdsWithMissingBibliomanId($page, $limit, $sorting), $sorting);
	}
	private function getIdsWithMissingBibliomanId(int $page = 1, int $limit = null, SortingDefinition $sorting = null): array {
		return $this->getIdsForDql("SELECT e.id FROM {$this->getEntityName()} e WHERE e.bibliomanId IS NULL ORDER BY $sorting", $page, $limit);
	}
	public function getCountWithMissingBibliomanId(): int {
		return $this->getCountForDql("SELECT COUNT(b.id) FROM {$this->getEntityName()} b WHERE b.bibliomanId IS NULL");
	}

	private function getIdsForDql(string $dql, $page = 1, $limit = null): array {
		return $this->setPagination($this->_em->createQuery($dql), $page, $limit)
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getResult('id');
	}

	public function getCountForDql(string $dql): int {
		return $this->_em->createQuery($dql)
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getSingleScalarResult();
	}

	public function createSortingDefinition(string $sorting): SortingDefinition {
		return new SortingDefinition($sorting ?: $this->defaultSortingField, self::ALIAS, $this->sortableFields);
	}
}
