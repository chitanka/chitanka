<?php namespace App\Entity;

/**
 *
 */
class TextCommentRepository extends EntityRepository {
	/**
	 * @param int $limit
	 */
	public function getLatest($limit = null) {
		return $this->getByIds($this->getLatestIdsByDate($limit), 'e.time DESC');
	}

	/**
	 * @param int $limit
	 */
	public function getLatestIdsByDate($limit = null) {
		$dql = sprintf('SELECT e.id FROM %s e WHERE e.is_shown = 1 ORDER BY e.time DESC', $this->getEntityName());
		$query = $this->_em->createQuery($dql)->setMaxResults($limit);

		return $query->getResult('id');
	}

	public function getByText($text) {
		$texts = $this->getQueryBuilder()
			->where('e.text = ?1')->setParameter(1, $text->getId())
			->orderBy('e.time', 'ASC')
			->getQuery()->getArrayResult();
		return WorkSteward::joinPersonKeysForTexts($texts);
	}

	/**
	 * @param string $orderBy
	 */
	public function getByIds($ids, $orderBy = null) {
		return WorkSteward::joinPersonKeysForWorks(parent::getByIds($ids, $orderBy));
	}

	public function getQueryBuilder($orderBys = null) {
		$qb = parent::getQueryBuilder($orderBys)
			->select('e', 't', 'a', 'ap', 's', 'u')
			->leftJoin('e.text', 't')
			->leftJoin('t.series', 's')
			->leftJoin('t.textAuthors', 'a')
			->leftJoin('a.person', 'ap')
			->leftJoin('e.user', 'u');

		return $qb;
	}

}
