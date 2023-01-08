<?php namespace App\Persistence;

/**
 *
 */
class SiteNoticeRepository extends EntityRepository {

	const RANDOM_CACHE_LIFETIME = 600;

	public function findForFrontPage() {
		return $this->findBy(['isActive' => true, 'isForFrontPage' => true]);
	}

	public function getGlobalRandom() {
		return $this->getRandom('e.isActive = 1 AND e.isForFrontPage = 0');
	}
}
