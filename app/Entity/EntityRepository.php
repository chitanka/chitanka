<?php namespace App\Entity;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

abstract class EntityRepository extends \Doctrine\ORM\EntityRepository {

	const ALIAS = 'e';
	const DEFAULT_CACHE_LIFETIME = 3600;
	const RANDOM_CACHE_LIFETIME = 3;

	protected $queryableFields = [];

	/**
	 * Save an entity into the database.
	 * @param object $entity
	 * @see \Doctrine\ORM\EntityManager::persist()
	 * @see \Doctrine\ORM\EntityManager::flush()
	 */
	public function save($entity) {
		$this->_em->persist($entity);
		$this->_em->flush();
	}

	/**
	 * Remove an entity from the database.
	 * @param object $entity
	 */
	public function delete($entity) {
		$this->_em->remove($entity);
		$this->_em->flush();
	}

	/**
	 * Flushes the entity manager queue.
	 * @see \Doctrine\ORM\EntityManager::flush()
	 */
	public function flush() {
		$this->getEntityManager()->flush();
	}

	/**
	 * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters
	 * and returns the number of affected rows.
	 *
	 * This method supports PDO binding types as well as DBAL mapping types.
	 *
	 * @param string $query  The SQL query.
	 * @param array  $params The query parameters.
	 * @param array  $types  The parameter types.
	 *
	 * @return integer The number of affected rows.
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @see \Doctrine\DBAL\Connection::executeUpdate
	 */
	public function execute($query, array $params = [], array $types = []) {
		return $this->getEntityManager()->getConnection()->executeUpdate($query, $params, $types);
	}

	/**
	 * A proxy to self::getCount()
	 * @param string $where
	 * @return int
	 */
	public function count($where = null) {
		return $this->getCount($where);
	}

	/**
	 * Get count of entities matching a given where clause
	 * @param string $where
	 * @return int
	 */
	public function getCount($where = null) {
		$qb = $this->createQueryBuilder(self::ALIAS)->select('COUNT('.self::ALIAS.'.id)');
		if ($where !== null) {
			$qb->andWhere($where);
		}
		return $qb->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getSingleScalarResult();
	}

	/**
	 * Set pagination parameters for a given query.
	 * @param \Doctrine\ORM\Query $query
	 * @param int $page
	 * @param int $limit
	 * @return \Doctrine\ORM\Query
	 */
	protected function setPagination($query, $page, $limit) {
		if ($limit > 0) {
			$query->setMaxResults($limit)->setFirstResult(($page - 1) * $limit);
		}

		return $query;
	}

	/**
	 * @param int $limit
	 * @param string $where
	 * @return Entity
	 */
	public function getRandomEntities($limit = 3, $where = null) {
		$cacheKey = static::class."_random_{$limit}_{$where}";
		return $this->fetchFromCache($cacheKey, function() use ($limit, $where) {
			$entities = [];
			while (count($entities) < $limit) {
				$randomEntity = $this->getRandom($where, 0);
				if ($randomEntity === null) {
					break;
				}
				$entities[$randomEntity->getId()] = $randomEntity;
			}
			return $entities;
		}, static::RANDOM_CACHE_LIFETIME);
	}

	/**
	 * @param string $where
	 * @param int $cacheLifetime
	 * @return Entity
	 */
	public function getRandom($where = null, $cacheLifetime = null) {
		return $this->getRandomQuery($where, null, $cacheLifetime)->getOneOrNullResult();
	}

	/**
	 * @param string $where
	 * @return int
	 */
	public function getRandomId($where = null) {
		return $this->getRandomQuery($where, self::ALIAS.'.id')->getSingleScalarResult();
	}

	/**
	 * @param string $where
	 * @param string $select
	 * @param int $cacheLifetime
	 * @return \Doctrine\ORM\Query
	 */
	protected function getRandomQuery($where = null, $select = null, $cacheLifetime = null) {
		$qb = $this->getEntityManager()->createQueryBuilder()
			->select($select ?: self::ALIAS)
			->from($this->getEntityName(), self::ALIAS);
		if ($where !== null) {
			$qb->andWhere($where);
		}
		$query = $qb->getQuery();
		$cacheKey = md5($query->getSQL());
		$cacheLifetime = $cacheLifetime !== null ? $cacheLifetime : static::RANDOM_CACHE_LIFETIME;
		$randomId = $this->fetchFromCache($cacheKey, function() use ($where) {
			return rand(1, $this->getCount($where)) - 1;
		}, $cacheLifetime);
		$query
			->setMaxResults(1)
			->setFirstResult($randomId)
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);

		return $query;
	}

	/**
	 * Finds entities by a set of criteria.
	 * Override to use cache.
	 *
	 * @param array      $criteria
	 * @param array|null $orderBy
	 * @param int|null   $limit
	 * @param int|null   $offset
	 *
	 * @return array The objects.
	 */
	public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null) {
		return $this->createFindQuery($criteria, $orderBy, $limit, $offset)->getResult();
	}

	/**
	 * Finds a single entity by a set of criteria.
	 * Override to use cache.
	 *
	 * @param array $criteria
	 * @param array|null $orderBy
	 * @return mixed
	 */
	public function findOneBy(array $criteria, array $orderBy = null) {
		return $this->createFindQuery($criteria, $orderBy, 1)->getOneOrNullResult();
	}

	protected function createFindQuery(array $criteria, array $orderBy = null, $limit = null, $offset = null) {
		$queryBuilder = $this->createQueryBuilder(self::ALIAS);
		$this->addCriteriaToQueryBuilder($queryBuilder, $criteria);
		$this->addOrderingToQueryBuilder($queryBuilder, $orderBy);
		$query = $this->addLimitingToQuery($queryBuilder->getQuery(), $limit, $offset);
		$query->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);
		return $query;
	}

	/**
	 *
	 * @param array $ids
	 * @param string $orderBy
	 * @return Entity[]
	 */
	public function findByIds(array $ids, $orderBy = null) {
		if (empty($ids)) {
			return [];
		}
		return $this->getQueryBuilder($orderBy)
			->where(sprintf(self::ALIAS.'.id IN (%s)', implode(',', $ids)))
			->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getResult();
	}

	/**
	 * @param array $ids
	 * @param string $orderBy
	 * @return array
	 */
	public function getByIds($ids, $orderBy = null) {
		if (empty($ids)) {
			return [];
		}

		return $this->getQueryBuilder($orderBy)
			->where(sprintf(self::ALIAS.'.id IN (%s)', implode(',', $ids)))
			->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getArrayResult();
	}

	/**
	 * @param array $params
	 * @return Entity[]
	 */
	public function findByQuery($params) {
		if ($query = $this->query($params)) {
			return $query->getResult();
		}
		return [];
	}

	/**
	 * @param array $params
	 * @return array
	 */
	public function getByQuery($params) {
		if ($query = $this->query($params)) {
			return $query->getArrayResult();
		}
		return [];
	}

	/**
	 * @param array $params
	 * @return \Doctrine\ORM\Query
	 */
	private function query($params) {
		if (empty($params['text']) || empty($params['by'])) {
			return null;
		}

		$params += ['match' => '*'];
		switch ($params['match']) {
			case 'exact':
				$op = '=';
				$param = $params['text'];
				break;
			case 'prefix':
				$op = 'LIKE';
				$param = "$params[text]%";
				break;
			case 'suffix':
				$op = 'LIKE';
				$param = "%$params[text]";
				break;
			default:
				$op = 'LIKE';
				$param = "%$params[text]%";
				break;
		}
		$tests = [];
		foreach (explode(',', $params['by']) as $field) {
			if (in_array($field, $this->queryableFields)) {
				$tests[] = self::ALIAS.".$field $op ?1";
			}
		}
		if (empty($tests)) {
			return null;
		}

		$query = $this->getQueryBuilder()
			->where(implode(' OR ', $tests))->setParameter(1, $param)
			->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);
		if (isset($params['limit'])) {
			$query->setMaxResults($params['limit']);
		}
		return $query;
	}

	public function getQueryableFields() {
		return $this->queryableFields;
	}

	/**
	 *
	 * @param string $orderBys
	 * @return QueryBuilder
	 */
	public function getQueryBuilder($orderBys = null) {
		$qb = $this->createQueryBuilder('e');

		if (!empty($orderBys)) {
			foreach (explode(',', $orderBys) as $orderBy) {
				$orderBy = ltrim($orderBy);
				if (strpos($orderBy, ' ') === false) {
					$field = $orderBy;
					$order = 'asc';
				} else {
					list($field, $order) = explode(' ', ltrim($orderBy));
				}
				if (strpos($field, '.') === false) {
					$field = self::ALIAS.".$field";
				}
				$qb->addOrderBy($field, $order);
			}
		}

		return $qb;
	}

	/**
	 * @param string $s
	 * @return string
	 */
	protected function stringForLikeClause($s) {
		return "%".str_replace(' ', '%', $s)."%";
	}

	protected function addCriteriaToQueryBuilder(QueryBuilder $queryBuilder, array $criteria) {
		foreach ($criteria as $field => $value) {
			$comparison = is_array($value) ? "IN (:{$field})" : "= :{$field}";
			$queryBuilder->andWhere(self::ALIAS . ".{$field} {$comparison}")->setParameter($field, $value);
		}
		return $queryBuilder;
	}

	protected function addOrderingToQueryBuilder(QueryBuilder $queryBuilder, array $ordering = null) {
		if ($ordering !== null) {
			foreach ($ordering as $field => $dir) {
				$queryBuilder->addOrderBy(self::ALIAS .'.'. $field, $dir);
			}
		}
		return $queryBuilder;
	}

	protected function addLimitingToQuery(Query $query, $limit, $offset = null) {
		if ($limit !== null) {
			$query->setMaxResults($limit);
		}
		if ($offset !== null) {
			$query->setFirstResult($offset);
		}
		return $query;
	}

	/**
	 * @param string $cacheKey
	 * @param callable $dataGenerator
	 * @param int $lifetime
	 * @return mixed
	 */
	protected function fetchFromCache($cacheKey, $dataGenerator, $lifetime = self::DEFAULT_CACHE_LIFETIME) {
		if (!$lifetime) {
			return $dataGenerator();
		}
		$cacheDriver = $this->_em->getConfiguration()->getQueryCacheImpl();
		$data = $cacheDriver->fetch($cacheKey);
		if (!$data) {
			$data = $dataGenerator();
			$cacheDriver->save($cacheKey, $data, $lifetime);
		}
		return $data;
	}
}
