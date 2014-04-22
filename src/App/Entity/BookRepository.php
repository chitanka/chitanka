<?php
namespace App\Entity;

/**
 * A repository for books
 */
class BookRepository extends EntityRepository
{
	protected $queryableFields = array('id', 'title', 'subtitle', 'orig_title');

	/** @return Book */
	public function get($id)
	{
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


	public function getByCategory($category, $page = 1, $limit = null)
	{
		$ids = $this->getIdsByCategory($category, $page, $limit);

		return empty($ids) ? array() : $this->getByIds($ids);
	}

	private function getIdsByCategory($category, $page = 1, $limit = null)
	{
		$dql = "SELECT b.id FROM {$this->getEntityName()} b WHERE b.category = {$category->getId()} ORDER BY b.title";
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);

		return $query->getResult('id');
	}

	public function getBySequence($sequence, $page = 1, $limit = null)
	{
		$ids = $this->getIdsBySequence($sequence, $page, $limit);

		return empty($ids) ? array() : $this->getByIds($ids, 'e.seqnr, e.title');
	}

	private function getIdsBySequence($sequence, $page = 1, $limit = null)
	{
		$dql = "SELECT b.id FROM {$this->getEntityName()} b WHERE b.sequence = {$sequence->getId()} ORDER BY b.seqnr, b.title";
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);

		return $query->getResult('id');
	}

	public function getByPrefix($prefix, $page = 1, $limit = null)
	{
		$ids = $this->getIdsByPrefix($prefix, $page, $limit);

		return empty($ids) ? array() : $this->getByIds($ids);
	}

	private function getIdsByPrefix($prefix, $page, $limit)
	{
		$where = $prefix ? "b.title LIKE '$prefix%'" : '1=1';
		$dql = "SELECT b.id FROM {$this->getEntityName()} b WHERE $where ORDER BY b.title";
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);

		return $query->getResult('id');
	}

	public function getByIds($ids, $orderBy = null)
	{
		return WorkSteward::joinPersonKeysForBooks(parent::getByIds($ids, $orderBy));
	}

	public function countByPrefix($prefix)
	{
		$where = $prefix ? "b.title LIKE '$prefix%'" : '1=1';
		$dql = "SELECT COUNT(b.id) FROM {$this->getEntityName()} b WHERE $where";
		$query = $this->_em->createQuery($dql);

		return $query->getSingleScalarResult();
	}

	public function getByTitles($title, $limit = null)
	{
		$q = $this->getQueryBuilder()
			->where('e.title LIKE ?1 OR e.subtitle LIKE ?1 OR e.orig_title LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($title))
			->getQuery();
		if ($limit) {
			$q->setMaxResults($limit);
		}
		return WorkSteward::joinPersonKeysForBooks($q->getArrayResult());
	}

	public function getByAuthor($author)
	{
		$books = $this->getQueryBuilder('s.name, e.seqnr, e.title')
			->andWhere('ap.id = ?1')->setParameter(1, $author->getId())
			->getQuery()
			->getArrayResult();
		return WorkSteward::joinPersonKeysForBooks($books);
	}

	public function getQueryBuilder($orderBys = null)
	{
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

	public function getWithMissingCover($page = 1, $limit = null)
	{
		$ids = $this->getIdsWithMissingCover($page, $limit);
		return empty($ids) ? array() : $this->getByIds($ids);
	}

	private function getIdsWithMissingCover($page = 1, $limit = null)
	{
		$dql = "SELECT b.id FROM {$this->getEntityName()} b WHERE b.has_cover = 0 ORDER BY b.title ASC";
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		return $query->getResult('id');
	}

	public function getCountWithMissingCover()
	{
		$dql = "SELECT COUNT(b.id) FROM {$this->getEntityName()} b WHERE b.has_cover = 0";
		$query = $this->_em->createQuery($dql);

		return $query->getSingleScalarResult();
	}
}
