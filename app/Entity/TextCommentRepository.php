<?php namespace App\Entity;

/**
 *
 */
class TextCommentRepository extends EntityRepository {
	/**
	 * @param int $limit
	 * @return TextComment[]
	 */
	public function getLatest($limit = null) {
		return $this->findByIds($this->getLatestIdsByDate($limit), 'e.time DESC');
	}

	/**
	 * @param int $limit
	 */
	public function getLatestIdsByDate($limit = null) {
		$dql = sprintf('SELECT e.id FROM %s e WHERE e.is_shown = 1 ORDER BY e.time DESC', $this->getEntityName());
		$query = $this->_em->createQuery($dql)->setMaxResults($limit);
		$query->useResultCache(true, static::DEFAULT_CACHE_LIFETIME);
		return $query->getResult('id');
	}

	public function getByText($text) {
		$texts = $this->getQueryBuilder()
			->where('e.text = ?1')->setParameter(1, $text->getId())
			->orderBy('e.time', 'ASC')
			->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getArrayResult();
		return WorkSteward::joinPersonKeysForTexts($texts);
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
