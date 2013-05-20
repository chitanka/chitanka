<?php
namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as BaseAdmin;
use Symfony\Component\Form\FormEvent;

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

	public function fixNewLines(FormEvent $event)
	{
		$data = $event->getData();
		foreach ($data as $field => $value) {
			$data[$field] = str_replace("\r\n", "\n", $value);
		}
		$event->setData($data);
	}
}
