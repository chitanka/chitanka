<?php namespace App\Persistence;

use App\Entity\Language;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 */
class LanguageRepository extends EntityRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, Language::class);
	}

}
