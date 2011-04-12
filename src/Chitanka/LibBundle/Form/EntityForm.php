<?php

namespace Chitanka\LibBundle\Form;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Form\Form;
use Doctrine\ORM\EntityManager;

abstract class EntityForm extends Form
{
	protected $entityName = null;

	protected function getRepository($entityName = null)
	{
		return $this->em->getRepository($this->getEntityName($entityName));
	}


	protected function getEntityName($entityName = null)
	{
		if (is_null($entityName)) {
			$entityName = $this->entityName;
		}

		return 'LibBundle:' . $entityName;
	}


	public function bindAndProcess(ParameterBag $params)
	{
		$this->bind($params);

		if ($this->isValid()) {
			$this->process();

			return true;
		}

		return false;
	}


	public function process()
	{
		$object = $this->getData();

		if (is_callable(array($object, 'process'))) {
			$object->process();
		} else if ($this->hasOption('em')) {
			$em = $this->getOption('em');
			$em->persist($tl);
			$em->flush();
		}
	}

}
