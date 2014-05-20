<?php namespace App\Controller\Admin;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Sonata\AdminBundle\Controller\CRUDController as BaseController;
use App\Entity\User;

class CRUDController extends BaseController {

	private $user;

	public function configure() {
		if ( ! $this->getUser()->inGroup('admin')) {
			throw new HttpException(401);
		}

		parent::configure();
	}

	/**
	 * @return User
	 */
	public function getUser() {
		return $this->user ?: $this->user = User::initUser($this->getRepository('User'));
	}

	/**
	 * @param string $entityName
	 */
	private function getRepository($entityName) {
		return $this->get('doctrine.orm.entity_manager')->getRepository('App:'.$entityName);
	}

}
