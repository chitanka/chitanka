<?php namespace App\Entity;

/**
 *
 */
class UserTextContribRepository extends EntityRepository {

	/**
	 * @param User $user
	 * @param int $limit
	 * @return array
	 */
	public function getLatestByUser(User $user, $limit = null) {
		return $this->getByUser($user, 1, $limit);
	}

	/**
	 * @param User $user
	 * @param int $page
	 * @param int $limit
	 * @return array
	 */
	public function getByUser(User $user, $page = 1, $limit = null) {
		$ids = $this->getIdsByUser($user, $page, $limit);

		return empty($ids) ? [] : $this->getByIds($ids);
	}

	/**
	 * @param User $user
	 * @param int $page
	 * @param int $limit
	 * @return array
	 */
	public function getIdsByUser(User $user, $page, $limit) {
		$dql = sprintf('SELECT c.id FROM %s c WHERE c.user = %d ORDER BY c.date DESC', $this->getEntityName(), $user->getId());
		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
		$ids = $query->getResult('id');

		return $ids;
	}

	/**
	 * @param User $user
	 * @return int
	 */
	public function countByUser(User $user) {
		return $this->getCount('e.user = '.$user->getId());
	}

	/**
	 * @param array $ids
	 * @param string $orderBy
	 * @return array
	 */
	public function getByIds($ids, $orderBy = null) {
		$texts = $this->getQueryBuilder()
			->where(sprintf('c.id IN (%s)', implode(',', $ids)))
			->getQuery()->getArrayResult();

		return WorkSteward::joinPersonKeysForWorks($texts);
	}

	/**
	 * @param string $orderBys
	 * @return \Doctrine\ORM\QueryBuilder
	 */
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
