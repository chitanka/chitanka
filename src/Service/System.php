<?php namespace App\Service;

use App\Persistence\EntityManager;
use App\Entity\User;

class System {

	/** @var EntityManager */
	private $em;

	public function __construct(EntityManager $em) {
		$this->em = $em;
	}

	public function closeUserAccount(User $user) {
		$user->closeAccount();
		// TODO delete everything
		$this->em->persist($user);
		$this->em->flush();
		return true;
	}

}
