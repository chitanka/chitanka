<?php namespace App\Entity;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 *
 */
class UserRepository extends EntityRepository implements UserProviderInterface {

	/**
	 * @param string $username
	 * @return User
	 */
	public function findByUsername($username) {
		$user = $this->findOneBy(['username' => $username]);
		if (!$user) {
			throw new UsernameNotFoundException("unknown username '$username'");
		}
		return $user;
	}

	/**
	 * @param string $usernameOrEmail
	 * @return User
	 */
	public function findByUsernameOrEmail($usernameOrEmail) {
		try {
			return $this->findByUsername($usernameOrEmail);
		} catch (UsernameNotFoundException $e) {
			$user = $this->findByEmail($usernameOrEmail);
			if (!$user) {
				throw new UsernameNotFoundException("unknown username or email '$usernameOrEmail'");
			}
		}
		return $user;
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
		return $this->findByUsername($username);
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
