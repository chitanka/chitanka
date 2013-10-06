<?php

namespace Chitanka\LibBundle\Entity;

/**
 *
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

	public function getIdsByCategory($category, $page = 1, $limit = null)
	{
		$dql = sprintf('SELECT b.id FROM %s b WHERE b.category = %d ORDER BY b.title', $this->getEntityName(), $category->getId());
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);

		return $query->getResult('id');
	}


	public function getBySequence($sequence, $page = 1, $limit = null)
	{
		$ids = $this->getIdsBySequence($sequence, $page, $limit);

		return empty($ids) ? array() : $this->getByIds($ids, 'e.seqnr, e.title');
	}

	public function getIdsBySequence($sequence, $page = 1, $limit = null)
	{
		$dql = sprintf('SELECT b.id FROM %s b WHERE b.sequence = %d ORDER BY b.seqnr, b.title', $this->getEntityName(), $sequence->getId());
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);

		return $query->getResult('id');
	}


	public function getByPrefix($prefix, $page = 1, $limit = null)
	{
		$ids = $this->getIdsByPrefix($prefix, $page, $limit);

		return empty($ids) ? array() : $this->getByIds($ids);
	}

	public function getIdsByPrefix($prefix, $page, $limit)
	{
		$where = $prefix ? "b.title LIKE '$prefix%'" : '1=1';
		$dql = sprintf('SELECT b.id FROM %s b WHERE %s ORDER BY b.title', $this->getEntityName(), $where);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);

		return $query->getResult('id');
	}

	public function getByIds($ids, $orderBy = null)
	{
		return $this->joinPersonKeysForBooks(parent::getByIds($ids, $orderBy));
	}

	public function countByPrefix($prefix)
	{
		$where = $prefix ? "b.title LIKE '$prefix%'" : '1=1';
		$dql = sprintf('SELECT COUNT(b.id) FROM %s b WHERE %s', $this->getEntityName(), $where);
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
		return $q->getArrayResult();
	}


	public function getByAuthor($author)
	{
		$books = $this->getQueryBuilder('s.name, e.seqnr, e.title')
			->andWhere('ap.id = ?1')->setParameter(1, $author->getId())
			->getQuery()
			->getArrayResult();
		return $this->joinPersonKeysForBooks($books);
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

	private function joinPersonKeysForBooks($books)
	{
		foreach ($books as $k => $book) {
			if (isset($book['bookAuthors'])) {
				$authors = array();
				foreach ($book['bookAuthors'] as $bookAuthor) {
					if ($bookAuthor['pos'] >= 0) {
						$authors[] = $bookAuthor['person'];
					}
				}
				$books[$k]['authors'] = $authors;
			}
		}
		return $books;
	}

}
