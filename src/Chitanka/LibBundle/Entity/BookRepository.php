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
			->select('b', 'a', 's', 'c', 'l', 'ls', 't', 'ta')
			->from($this->getEntityName(), 'b')
			->leftJoin('b.authors', 'a')
			->leftJoin('b.sequence', 's')
			->leftJoin('b.category', 'c')
			->leftJoin('b.links', 'l')
			->leftJoin('l.site', 'ls')
			->leftJoin('b.texts', 't')
			->leftJoin('t.authors', 'ta')
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
		$dql = sprintf('SELECT b.id FROM %s b WHERE b.removedNotice IS NULL AND b.category = %d ORDER BY b.title', $this->getEntityName(), $category->getId());
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
		$dql = sprintf('SELECT b.id FROM %s b WHERE b.removedNotice IS NULL AND b.sequence = %d ORDER BY b.seqnr, b.title', $this->getEntityName(), $sequence->getId());
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
		$where = $prefix ? "AND b.title LIKE '$prefix%'" : '';
		$dql = sprintf('SELECT b.id FROM %s b WHERE b.removedNotice IS NULL %s ORDER BY b.title', $this->getEntityName(), $where);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);

		return $query->getResult('id');
	}


	public function countByPrefix($prefix)
	{
		$where = $prefix ? "AND b.title LIKE '$prefix%'" : '';
		$dql = sprintf('SELECT COUNT(b.id) FROM %s b WHERE b.removedNotice IS NULL %s', $this->getEntityName(), $where);
		$query = $this->_em->createQuery($dql);

		return $query->getSingleScalarResult();
	}


	public function getByTitles($title, $limit = null)
	{
		return $this->getQueryBuilder()
			->where('e.title LIKE ?1 OR e.subtitle LIKE ?1 OR e.orig_title LIKE ?1')
			->setParameter(1, "%$title%")
			->getQuery()//->setMaxResults($limit)
			->getArrayResult();
	}


	public function getByAuthor($author)
	{
		return $this->getQueryBuilder()
			->andWhere('a.id = ?1')->setParameter(1, $author->getId())
			->getQuery()
			->getArrayResult();
	}

	public function getQueryBuilder($orderBys = null)
	{
		if (is_null($orderBys)) {
			$orderBys = 'e.title';
		}
		$qb = parent::getQueryBuilder($orderBys)
			->addSelect('a', 's', 'c')
			->leftJoin('e.authors', 'a')
			->leftJoin('e.sequence', 's')
			->leftJoin('e.category', 'c')
			->where("e.removedNotice IS NULL");

		return $qb;
	}

}
