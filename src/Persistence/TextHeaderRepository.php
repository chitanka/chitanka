<?php namespace App\Persistence;

use App\Entity\TextHeader;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 */
class TextHeaderRepository extends EntityRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, TextHeader::class);
	}

}
