<?php namespace App\Persistence;

use App\Entity\Thread;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 */
class ThreadRepository extends EntityRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, Thread::class);
	}

}
