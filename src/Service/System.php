<?php namespace App\Service;

use App\Entity\User;
use App\Persistence\UserRepository;

class System {

	private $userRepository;

	public function __construct(UserRepository $userRepository) {
		$this->userRepository = $userRepository;
	}

	public function closeUserAccount(User $user) {
		$user->closeAccount();
		// TODO delete everything
		$this->userRepository->save($user);
		return true;
	}

}
