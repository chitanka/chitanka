<?php

namespace Chitanka\LibBundle\Entity;

class PersonRepository extends EntityRepository
{
	protected
		$asAuthor = false,
		$asTranslator = false,
		$queryableFields = array('id', 'slug', 'name', 'orig_name', 'real_name', 'oreal_name');


	public function getBy($filters, $page = 1, $limit = null)
	{
		$query = $this->setPagination($this->getQueryBy($filters), $page, $limit);

		return $query->getArrayResult();
	}

	public function countBy($filters)
	{
		return $this->getCountQueryBy($filters)->getSingleScalarResult();
	}

	public function getBySlug($slug)
	{
		return $this->findOneBy(array('slug' => $slug));
	}


	public function getQueryBy($filters)
	{
		$qb = $this->getQueryBuilder();
		$qb = $this->addFilters($qb, $filters);

		return $qb->getQuery();
	}

	public function getCountQueryBy($filters)
	{
		$qb = $this->getCountQueryBuilder();
		$qb = $this->addFilters($qb, $filters);

		return $qb->getQuery();
	}


	public function getQueryBuilder($orderBys = null)
	{
		$qb = $this->getBaseQueryBuilder('e')
			->select('e', 'p')
			->leftJoin('e.person', 'p');

		return $qb;
	}

	public function getCountQueryBuilder($alias = 'e')
	{
		$qb = $this->getBaseQueryBuilder($alias)->select('COUNT(e.id)');

		return $qb;
	}

	public function getBaseQueryBuilder($alias = 'e')
	{
		$qb = $this->createQueryBuilder($alias);
		if ($this->asAuthor) {
			$qb->andWhere("e.is_author = 1");
		}
		if ($this->asTranslator) {
			$qb->andWhere("e.is_translator = 1");
		}

		return $qb;
	}

	public function getCount($where = array())
	{
		return $this->getCountQueryBuilder()->andWhere($where)
			->getQuery()->getSingleScalarResult();
	}

	public function getCountsByCountry()
	{
		return $this->getCountQueryBuilder()
			->select('e.country', 'COUNT(e.id)')
			->groupBy('e.country')
			->getQuery()->getResult('key_value');
	}


	public function getByNames($name, $limit = null)
	{
		return $this->getQueryBuilder()
			->where('e.name LIKE ?1 OR e.orig_name LIKE ?1')
			->setParameter(1, "%$name%")
			->getQuery()//->setMaxResults($limit)
			->getArrayResult();
	}


	public function asAuthor()
	{
		$this->asAuthor = true;
		$this->asTranslator = false;

		return $this;
	}

	public function asTranslator()
	{
		$this->asTranslator = true;
		$this->asAuthor = false;

		return $this;
	}


	public function addFilters($qb, $filters)
	{
		$nameField = empty($filters['by']) || $filters['by'] == 'first' ? 'e.name' : 'e.last_name';
		$qb->addOrderBy($nameField);
		if ( ! empty($filters['prefix']) && $filters['prefix'] != '-' ) {
			$qb->andWhere("$nameField LIKE :name")->setParameter('name', $filters['prefix'].'%');
		}

		if ( ! empty($filters['country']) ) {
			$qb->andWhere('e.country = ?1')->setParameter(1, $filters['country']);
		}

		return $qb;
	}
}
