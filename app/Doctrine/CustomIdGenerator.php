<?php namespace App\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use App\Entity\NextIdRepository;

class CustomIdGenerator extends AbstractIdGenerator {
	public function generate(EntityManager $em, $entity) {
		return $this->createNextIdRepository($em)->selectNextId($entity);
	}

	protected function createNextIdRepository(EntityManager $em) {
		return new NextIdRepository($em, new ClassMetadata('App\Entity\NextId'));
	}

}
