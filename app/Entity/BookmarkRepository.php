<?php namespace App\Entity;

/**
 *
 */
class BookmarkRepository extends EntityRepository {
	public function getLatestByUser($user, $limit = null) {
		return $this->getByUser($user, 1, $limit, 'e.created_at DESC');
	}

	public function getByUser($user, $page = 1, $limit = null, $orderBys = 't.title ASC') {
		$ids = $this->getIdsByUser($user, $page, $limit, $orderBys);

		return empty($ids) ? array() : $this->getByIds($ids, $orderBys);
	}

	/**
	 * @param int $page
	 */
	public function getIdsByUser($user, $page, $limit, $orderBys = 't.title ASC') {
		$dql = sprintf('SELECT e.id FROM %s e LEFT JOIN e.text t WHERE e.user = %d ORDER BY %s', $this->getEntityName(), $user->getId(), $orderBys);
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$ids = $query->getResult('id');

		return $ids;
	}

	public function countByUser($user) {
		return $this->getCount('e.user = '.$user->getId());
	}

	/**
	 * RAW_SQL
	 * @param User $user
	 */
	public function getValidTextIds($user, $textIds) {
		if (is_array($textIds)) {
			$textIds = implode(',', $textIds);
		} else {
			$textIds = preg_replace('/[^\d,]/', '', $textIds);
			$textIds = preg_replace('/,,+/', ',', trim($textIds, ','));
		}

		if (empty($textIds)) {
			return array();
		}

		$sql = sprintf('SELECT text_id FROM %s WHERE user_id = %d AND text_id IN (%s)', $this->getClassMetadata()->getTableName(), $user->getId(), $textIds);
		$validTextIds = $this->_em->getConnection()->executeQuery($sql)->fetchAll(\PDO::FETCH_COLUMN);

		return $validTextIds;
	}

	/**
	 * @param string $orderBy
	 */
	public function getByIds($ids, $orderBy = null) {
		return WorkSteward::joinPersonKeysForWorks(parent::getByIds($ids, $orderBy));
	}

	public function getQueryBuilder($orderBys = null) {
		return parent::getQueryBuilder($orderBys)
			->addSelect('t', 's', 'a', 'ap')
			->leftJoin('e.text', 't')
			->leftJoin('t.series', 's')
			->leftJoin('t.textAuthors', 'a')
			->leftJoin('a.person', 'ap');
	}

}
