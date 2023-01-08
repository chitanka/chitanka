<?php namespace App\Persistence;

use App\Entity\SiteNotice;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 */
class SiteNoticeRepository extends EntityRepository {

	const RANDOM_CACHE_LIFETIME = 600;

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, SiteNotice::class);
	}

	public function findForFrontPage() {
		return $this->findBy(['isActive' => true, 'isForFrontPage' => true]);
	}

	public function getGlobalRandom() {
		return $this->getRandom('e.isActive = 1 AND e.isForFrontPage = 0');
	}
}
