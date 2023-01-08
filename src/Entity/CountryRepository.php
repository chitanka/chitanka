<?php namespace App\Entity;

/**
 *
 */
class CountryRepository extends EntityRepository {

	/**
	 * @return Country[]
	 */
	public function findAll() {
		return $this->findBy([], ['name' => 'asc']);
	}

	/**
	 * @param string $code
	 * @return Country
	 */
	public function findByCode($code) {
		return $this->findOneBy(['code' => $code]);
	}

}
