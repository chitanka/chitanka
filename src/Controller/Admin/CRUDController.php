<?php namespace App\Controller\Admin;

use App\Entity\User;
use Sonata\AdminBundle\Controller\CRUDController as BaseController;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CRUDController extends BaseController {

	public function configure() {
		if ( ! $this->isAuthorized()) {
			throw new HttpException(401);
		}
		parent::configure();
		$this->getDoctrine()->getManager()->getConfiguration()->setSecondLevelCacheEnabled(false);
	}

	/**
	 * @return User
	 */
	public function getUser() {
		return $this->get('security.token_storage')->getToken()->getUser();
	}

	protected function isAuthorized() {
		return $this->getUser()->inGroup(User::GROUP_ADMIN);
	}
}
