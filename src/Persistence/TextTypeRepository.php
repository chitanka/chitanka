<?php namespace App\Persistence;

use App\Entity\TextType;

/**
 *
 */
class TextTypeRepository extends EntityRepository {

	/**
	 * @return TextType[]
	 */
	public function findAll() {
		return $this->findBy([], ['name' => 'asc']);
	}

}
