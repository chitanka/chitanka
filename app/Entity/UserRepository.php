<?php namespace App\Entity;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 *
 */
class UserRepository extends EntityRepository implements UserProviderInterface {

	/**
	 * @return User
	 */
	public function loadUserByUsername($username) {
		$user = $this->findByUsername($username);
		if ( ! $user) {
			throw new UsernameNotFoundException;
		}

		return $user;
	}

	/**
	 * @return User
	 */
	public function findByUsername($username) {
		return $this->findOneBy(array('username' => $username));
	}

	/**
	 * @return User[]
	 */
	public function findByUsernames(array $usernames) {
		return $this->findBy(array('username' => $usernames));
	}

	/**
	 * @return User
	 */
	public function findByToken($token) {
		return $this->findOneBy(array('token' => $token));
	}

	/**
	 * @return User
	 */
	public function refreshUser(UserInterface $user) {
		return $user;
	}

	/**
	 * @return bool
	 */
	public function supportsClass($class) {
		return false;
	}
}
