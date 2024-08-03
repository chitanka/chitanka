<?php namespace App\Persistence;

use App\Entity\TextType;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 */
class TextTypeRepository extends EntityRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, TextType::class);
	}

	/**
	 * @return TextType[]
	 */
	public function findAll() {
		return $this->findBy([], ['name' => 'asc']);
	}

}
