<?php namespace App\Persistence;

use App\Entity\WikiSite;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 */
class WikiSiteRepository extends EntityRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, WikiSite::class);
	}

	public function findSiteByCode($code) {
		return $this->findOneBy(['code' => $code]);
	}
}
