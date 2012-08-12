<?php

namespace Chitanka\LibBundle\Entity;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 *
 */
class UserRepository extends EntityRepository implements UserProviderInterface
{
	public function loadUserByUsername($username)
	{
		$user = $this->findOneBy(array('username' => $username));
		if ( ! $user) {
			throw new UsernameNotFoundException;
		}

		return $user;
	}

    public function refreshUser(UserInterface $user)
    {
      return $user;
    }

	public function supportsClass($class)
	{
		return false;
	}
}
