<?php namespace App\Persistence;

use App\Entity\Country;

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
