<?php namespace App\Entity;

/**
 *
 */
class BookmarkRepository extends EntityRepository {

	/**
	 * @param User $user
	 * @param int $limit
	 * @return array
	 */
	public function getLatestByUser(User $user, $limit = null) {
		return $this->getByUser($user, 1, $limit, 'e.createdAt DESC');
	}

	/**
	 * @param User $user
	 * @param int $page
	 * @param int $limit
	 * @param string $orderBys
	 * @return array
	 */
	public function getByUser(User $user, $page = 1, $limit = null, $orderBys = 't.title ASC') {
		$ids = $this->getIdsByUser($user, $page, $limit, $orderBys);

		return empty($ids) ? [] : $this->findByIds($ids, $orderBys);
	}

	/**
	 * @param User $user
	 * @param int $page
	 * @param int $limit
	 * @param string $orderBys
	 * @return array
	 */
	public function getIdsByUser(User $user, $page, $limit, $orderBys = 't.title ASC') {
		$dql = sprintf('SELECT e.id FROM %s e LEFT JOIN e.text t WHERE e.user = %d ORDER BY %s', $this->getEntityName(), $user->getId(), $orderBys);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$query->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);
		$ids = $query->getResult('id');
		return $ids;
	}

	/**
	 * @param User $user
	 * @return int
	 */
	public function countByUser($user) {
		return $this->getCount('e.user = '.$user->getId());
	}

	/**
	 * RAW_SQL
	 * @param User $user
	 * @param array|string $textIds
	 * @return array
	 */
	public function getValidTextIds($user, $textIds) {
		if (is_array($textIds)) {
			$textIds = implode(',', $textIds);
		}
		$textIds = preg_replace('/[^\d,]/', '', $textIds);
		$textIds = preg_replace('/,,+/', ',', trim($textIds, ','));

		if (empty($textIds)) {
			return [];
		}

		$sql = sprintf('SELECT text_id FROM %s WHERE user_id = %d AND text_id IN (%s)', $this->getClassMetadata()->getTableName(), $user->getId(), $textIds);
		$validTextIds = $this->_em->getConnection()->executeQuery($sql)->fetchAll(\PDO::FETCH_COLUMN);

		return $validTextIds;
	}

	/**
	 * @param string $orderBys
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getQueryBuilder($orderBys = null) {
		return parent::getQueryBuilder($orderBys)
			->addSelect('t', 's', 'a', 'ap')
			->leftJoin('e.text', 't')
			->leftJoin('t.series', 's')
			->leftJoin('t.textAuthors', 'a')
			->leftJoin('a.person', 'ap');
	}

}
