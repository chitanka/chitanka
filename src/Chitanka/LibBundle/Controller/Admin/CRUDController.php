<?php

namespace Chitanka\LibBundle\Controller\Admin;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Sonata\AdminBundle\Controller\CRUDController as BaseController;
use Chitanka\LibBundle\Entity\User;

class CRUDController extends BaseController {
	public function configure() {
		if ( ! $this->getUser()->inGroup('admin')) {
			throw new HttpException(401);
		}

		parent::configure();
	}

	public function getUser() {
		if ( ! isset($this->_user)) {
			$this->_user = User::initUser($this->getRepository('User'));
		}

		return $this->_user;
	}

	public function getRepository($entityName) {
		return $this->get('doctrine.orm.entity_manager')->getRepository('App:'.$entityName);
	}

}
