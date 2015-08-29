<?php namespace App\Entity;

abstract class EntityRepository extends \Doctrine\ORM\EntityRepository {

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
		$qb = $this->createQueryBuilder('e')->select('COUNT(e.id)');
		if ($where) {
			$qb->andWhere($where);
		}
		return $qb->getQuery()->getSingleScalarResult();
	}

	/**
	 * Set pagination parameters for a given query.
	 * @param \Doctrine\ORM\Query $query
	 * @param int $page
	 * @param int $limit
	 * @return \Doctrine\ORM\Query
	 */
	protected function setPagination($query, $page, $limit) {
		if ($limit) {
			$query->setMaxResults($limit)->setFirstResult(($page - 1) * $limit);
		}

		return $query;
	}

	/**
	 * @param string $where
	 * @return Entity
	 */
	public function getRandom($where = null) {
		try {
			return $this->getRandomQuery($where)->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/**
	 * @param string $where
	 * @return int
	 */
	public function getRandomId($where = null) {
		return $this->getRandomQuery($where, 'e.id')->getSingleScalarResult();
	}

	/**
	 * @param string $where
	 * @param string $select
	 * @return \Doctrine\ORM\Query
	 */
	protected function getRandomQuery($where = null, $select = null) {
		$qb = $this->getEntityManager()->createQueryBuilder()
			->select($select ?: 'e')
			->from($this->getEntityName(), 'e');
		if ($where) {
			$qb->andWhere($where);
		}
		$query = $qb->getQuery()
			->setMaxResults(1)
			->setFirstResult(rand(1, $this->getCount($where)) - 1);

		return $query;
	}

	/**
	 *
	 * @param array $ids
	 * @return Entity[]
	 */
	public function findByIds(array $ids) {
		if (empty($ids)) {
			return [];
		}
		return $this->getQueryBuilder()
			->where(sprintf('e.id IN (%s)', implode(',', $ids)))
			->getQuery()->getResult();
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
			->where(sprintf('e.id IN (%s)', implode(',', $ids)))
			->getQuery()->getArrayResult();
	}

	/**
	 * @param array $params
	 * @return array
	 */
	public function getByQuery($params) {
		if (empty($params['text']) || empty($params['by'])) {
			return [];
		}

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
				$tests[] = "e.$field $op ?1";
			}
		}
		if (empty($tests)) {
			return [];
		}

		$query = $this->getQueryBuilder()
			->where(implode(' OR ', $tests))->setParameter(1, $param)
			->getQuery();
		if (isset($params['limit'])) {
			$query->setMaxResults($params['limit']);
		}

		return $query->getArrayResult();
	}

	public function getQueryableFields() {
		return $this->queryableFields;
	}

	/**
	 *
	 * @param string $orderBys
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getQueryBuilder($orderBys = null) {
		$qb = $this->createQueryBuilder('e');

		if ($orderBys) {
			foreach (explode(',', $orderBys) as $orderBy) {
				$orderBy = ltrim($orderBy);
				if (strpos($orderBy, ' ') === false) {
					$field = $orderBy;
					$order = 'asc';
				} else {
					list($field, $order) = explode(' ', ltrim($orderBy));
				}
				if (strpos($field, '.') === false) {
					$field = "e.$field";
				}
				$qb->addOrderBy($field, $order);
			}
		}

		return $qb;
	}

	/**
	 * @param string $s
	 */
	protected function stringForLikeClause($s) {
		return "%".str_replace(' ', '%', $s)."%";
	}
}
