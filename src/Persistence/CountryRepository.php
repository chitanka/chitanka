<?php namespace App\Persistence;

use App\Entity\Country;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 */
class CountryRepository extends EntityRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, Country::class);
	}

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
