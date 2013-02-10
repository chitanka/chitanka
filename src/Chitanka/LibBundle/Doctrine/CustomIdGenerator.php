<?php
namespace Chitanka\LibBundle\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Chitanka\LibBundle\Entity\NextIdRepository;

class CustomIdGenerator extends AbstractIdGenerator
{
	public function generate(EntityManager $em, $entity)
	{
		return $this->createNextIdRepository($em)->selectNextId($entity);
	}

	protected function createNextIdRepository(EntityManager $em)
	{
		return new NextIdRepository($em, new ClassMetadata('Chitanka\LibBundle\Entity\NextId'));
	}

}
