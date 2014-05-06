<?php namespace App\Entity;

/**
 *
 */
class TextLabelLogRepository extends EntityRepository {

	public function getAll($page = 1, $limit = 30) {
		$query = $this->createDefaultQueryBuilder('log')->getQuery();
		$this->setPagination($query, $page, $limit);
		return $query->getArrayResult();
	}

	public function getForText(Text $text) {
		$query = $this->createDefaultQueryBuilder('log')
			->where('log.text = ?1')->setParameter(1, $text)
			->getQuery();
		return $query->getArrayResult();
	}

	private function createDefaultQueryBuilder($alias = 'log') {
		return $this->createQueryBuilder($alias)
			->select($alias, 'text', 'label', 'user')
			->leftJoin("$alias.text", 'text')
			->leftJoin("$alias.label", 'label')
			->leftJoin("$alias.user", 'user')
			->addOrderBy("$alias.date", 'desc');
	}
}
