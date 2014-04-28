<?php namespace App\Entity;

/**
 *
 */
class UserTextContribRepository extends EntityRepository {

	public function getLatestByUser($user, $limit = null) {
		return $this->getByUser($user, 1, $limit);
	}

	public function getByUser($user, $page = 1, $limit = null) {
		$ids = $this->getIdsByUser($user, $page, $limit);

		return empty($ids) ? array() : $this->getByIds($ids);
	}

	/**
	 * @param int $page
	 */
	public function getIdsByUser($user, $page, $limit) {
		$dql = sprintf('SELECT c.id FROM %s c WHERE c.user = %d ORDER BY c.date DESC', $this->getEntityName(), $user->getId());
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$ids = $query->getResult('id');

		return $ids;
	}

	public function countByUser($user) {
		return $this->getCount('e.user = '.$user->getId());
	}

	public function getByIds($ids, $orderBy = null) {
		$texts = $this->getQueryBuilder()
			->where(sprintf('c.id IN (%s)', implode(',', $ids)))
			->getQuery()->getArrayResult();

		return WorkSteward::joinPersonKeysForWorks($texts);
	}

	public function getQueryBuilder($orderBys = null) {
		return $this->_em->createQueryBuilder()
			->select('c', 't', 'a', 'ap', 's')
			->from($this->getEntityName(), 'c')
			->leftJoin('c.text', 't')
			->leftJoin('t.series', 's')
			->leftJoin('t.textAuthors', 'a')
			->leftJoin('a.person', 'ap')
			->orderBy('c.date', 'DESC');
	}

}
