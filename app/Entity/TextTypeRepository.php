<?php namespace App\Entity;

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
