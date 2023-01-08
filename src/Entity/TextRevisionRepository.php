<?php namespace App\Entity;

/**
 *
 */
class TextRevisionRepository extends RevisionRepository {
	public function getQueryBuilder($orderBys = null) {
		$qb = parent::getQueryBuilder($orderBys)
			->select('r', 't', 'a', 's', 'ap')
			->from($this->getEntityName(), 'r')
			->leftJoin('r.text', 't')
			->leftJoin('t.textAuthors', 'a')
			->leftJoin('t.series', 's')
			->leftJoin('a.person', 'ap');

		return $qb;
	}

}
