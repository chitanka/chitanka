<?php namespace App\Persistence;

/**
 *
 */
class WikiSiteRepository extends EntityRepository {

	public function findSiteByCode($code) {
		return $this->findOneBy(['code' => $code]);
	}
}
