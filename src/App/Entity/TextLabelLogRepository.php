<?php

namespace App\Entity;

/**
 *
 */
class TextLabelLogRepository extends EntityRepository {

	public function getAll($page = 1, $limit = 30) {
		$query = $this->createQueryBuilder('log')
			->select('log', 'text', 'label', 'user')
			->leftJoin('log.text', 'text')
			->leftJoin('log.label', 'label')
			->leftJoin('log.user', 'user')
			->addOrderBy('log.date', 'desc')
			->getQuery();
		$this->setPagination($query, $page, $limit);
		return $query->getArrayResult();
	}

	public function getForText(Text $text) {
		$query = $this->createQueryBuilder('log')
			->select('log', 'text', 'label', 'user')
			->leftJoin('log.text', 'text')
			->leftJoin('log.label', 'label')
			->leftJoin('log.user', 'user')
			->where('log.text = ?1')->setParameter(1, $text)
			->addOrderBy('log.date', 'asc')
			->getQuery();
		return $query->getArrayResult();
	}

}
