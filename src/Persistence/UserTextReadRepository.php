<?php namespace App\Persistence;

use App\Entity\UserTextRead;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 */
class UserTextReadRepository extends BookmarkRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, UserTextRead::class);
	}

}
