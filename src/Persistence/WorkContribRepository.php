<?php namespace App\Persistence;

use App\Entity\WorkContrib;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 */
class WorkContribRepository extends EntityRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, WorkContrib::class);
	}

}
