<?php

namespace Chitanka\LibBundle\Entity;

class SeriesRepository extends EntityRepository
{
	protected $queryableFields = array('id', 'slug', 'name', 'orig_name');

	public function findBySlug($slug)
	{
		return $this->findOneBy(array('slug' => $slug));
	}

	public function getByPrefix($prefix, $page = 1, $limit = null)
	{
		$ids = $this->getIdsByPrefix($prefix, $page, $limit);

		return empty($ids) ? array() : $this->getByIds($ids);
	}

	public function getIdsByPrefix($prefix, $page, $limit)
	{
		$where = $prefix ? "s.name LIKE '$prefix%'" : "s.name != ''";
		$dql = sprintf('SELECT s.id FROM %s s WHERE %s ORDER BY s.name', $this->getEntityName(), $where);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);

		return $query->getResult('id');
	}


	public function getByIds($ids, $orderBy = null)
	{
		$texts = $this->getQueryBuilder()
			->where(sprintf('e.id IN (%s)', implode(',', $ids)))
			->getQuery()->getArrayResult();

		return $texts;
	}

	public function countByPrefix($prefix)
	{
		$where = $prefix ? "s.name LIKE '$prefix%'" : "s.name != ''";
		$dql = sprintf('SELECT COUNT(s.id) FROM %s s WHERE %s', $this->getEntityName(), $where);
		$query = $this->_em->createQuery($dql);

		return $query->getSingleScalarResult();
	}


	public function getByNames($name, $limit = null)
	{
		return $this->getQueryBuilder()
			->where('e.name LIKE ?1 OR e.orig_name LIKE ?1')
			->setParameter(1, "%$name%")
			->getQuery()//->setMaxResults($limit)
			->getArrayResult();
	}

	public function getQueryBuilder($orderBys = null)
	{
		return $this->_em->createQueryBuilder()
			->select('e', 'a')
			->from($this->getEntityName(), 'e')
			->leftJoin('e.authors', 'a')
			->addOrderBy('e.name');
	}

}
