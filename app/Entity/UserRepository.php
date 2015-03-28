<?php namespace App\Entity;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 *
 */
class UserRepository extends EntityRepository implements UserProviderInterface {

	/**
	 * @param string $username
	 * @return User
	 */
	public function findByUsername($username) {
		return $this->findOneBy(['username' => $username]);
	}

	/**
	 * @param array $usernames
	 * @return User[]
	 */
	public function findByUsernames(array $usernames) {
		return $this->findBy(['username' => $usernames]);
	}

	/**
	 * @param string $token
	 * @return User
	 */
	public function findByToken($token) {
		return $this->findOneBy(['token' => $token]);
	}

	/**
	 * @param string $email
	 * @return User
	 */
	public function findByEmail($email) {
		return $this->findOneBy(['email' => $email]);
	}

	/** {@inheritdoc} */
	public function loadUserByUsername($username) {
		$user = $this->findByUsername($username);
		if (!$user) {
			throw new UsernameNotFoundException;
		}

		return $user;
	}

	/** {@inheritdoc} */
	public function refreshUser(UserInterface $user) {
		return $user;
	}

	/** {@inheritdoc} */
	public function supportsClass($class) {
		return false;
	}
}
