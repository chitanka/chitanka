<?php namespace App\Entity;

/**
 *
 */
class WikiSiteRepository extends EntityRepository {

	public function findSiteByCode($code) {
		return $this->findOneBy(array('code' => $code));
	}
}
