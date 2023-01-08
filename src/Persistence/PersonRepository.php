<?php namespace App\Persistence;

use App\Entity\Person;

/**
 *
 */
class PersonRepository extends EntityRepository {
	protected $asAuthor = false;
	protected $asTranslator = false;
	protected $queryableFields = ['id', 'slug', 'name', 'origName', 'realName', 'orealName'];

	/**
	 * @param string $slug
	 * @return Person
	 */
	public function findBySlug($slug) {
		return $this->findOneBy(['slug' => $slug]);
	}

	/**
	 * @param array $filters
	 * @param int $limit
	 * @param int $page
	 * @return Person[]
	 */
	public function getBy($filters, $page = 1, $limit = null) {
		$query = $this->setPagination($this->getQueryBy($filters), $page, $limit);
		$query->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);
		return $query->getResult();
	}

	public function countBy($filters) {
		return $this->getCountQueryBy($filters)->getSingleScalarResult();
	}

	public function getBySlug($slug) {
		return $this->findOneBy(['slug' => $slug]);
	}

	public function getQueryBy($filters) {
		$qb = $this->getQueryBuilder();
		$qb = $this->addFilters($qb, $filters);
		$query = $qb->getQuery();
		$query->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);
		return $query;
	}

	public function getCountQueryBy($filters) {
		$qb = $this->getCountQueryBuilder();
		$qb = $this->addFilters($qb, $filters);
		$query = $qb->getQuery();
		$query->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);
		return $query;
	}

	public function getQueryBuilder($orderBys = null) {
		$qb = $this->getBaseQueryBuilder('e')
			->select('e', 'p', 'c', 'pc')
			->leftJoin('e.person', 'p')
			->leftJoin('e.country', 'c')
			->leftJoin('p.country', 'pc');
		return $qb;
	}

	public function getCountQueryBuilder($alias = 'e') {
		return $this->getBaseQueryBuilder($alias)->select('COUNT(e.id)');
	}

	public function getBaseQueryBuilder($alias = 'e') {
		$qb = $this->createQueryBuilder($alias);
		if ($this->asAuthor) {
			$qb->andWhere("e.isAuthor = 1");
		}
		if ($this->asTranslator) {
			$qb->andWhere("e.isTranslator = 1");
		}
		return $qb;
	}

	public function getCount($where = null) {
		$qb = $this->getCountQueryBuilder();
		if ($where !== null) {
			$qb->andWhere($where);
		}
		$query = $qb->getQuery();
		$query->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);
		return $query->getSingleScalarResult();
	}

	/**
	 * @param string $name
	 * @param int $limit
	 * @return array
	 */
	public function getByNames($name, $limit = null) {
		$q = $this->getQueryBuilder()
			->where('e.name LIKE ?1 OR e.origName LIKE ?1 OR e.realName LIKE ?1 OR e.orealName LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($name))
			->getQuery();
		$this->addLimitingToQuery($q, $limit);
		$q->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);
		return $q->getArrayResult();
	}

	/** @return $this */
	public function asAuthor() {
		$this->asAuthor = true;
		$this->asTranslator = false;
		return $this;
	}

	/** @return $this */
	public function asTranslator() {
		$this->asTranslator = true;
		$this->asAuthor = false;
		return $this;
	}

	/**
	 * @param \Doctrine\ORM\QueryBuilder $qb
	 * @param array $filters
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function addFilters($qb, $filters) {
		$nameField = empty($filters['by']) || $filters['by'] == 'first-name' ? 'e.name' : 'e.lastName';
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
