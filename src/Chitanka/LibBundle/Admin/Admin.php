<?php
namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as BaseAdmin;

abstract class Admin extends BaseAdmin
{

	public function getEntityManager()
	{
		return $this->modelManager->getEntityManager($this->getClass());
	}

	public function getRepository()
	{
		return $this->getEntityManager()->getRepository($this->getClass());
	}
}
