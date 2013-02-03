<?php
namespace Chitanka\LibBundle\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Chitanka\LibBundle\Entity\NextId;

class CustomIdGenerator extends AbstractIdGenerator
{

	public function generate(EntityManager $em, $entity)
	{
		$nextId = $this->findNextId($em, $entity);
		$entityId = $nextId->getValue();
		$this->incrementAndSaveNextId($em, $nextId);

		return $entityId;
	}

	/** @return NextId */
	private function findNextId(EntityManager $em, $entity)
	{
		$nextId = $em->find('LibBundle:NextId', get_class($entity));
		if ($nextId == false) {
			$nextId = new NextId(get_class($entity));
			$nextId->setValue($this->getMaxIdForEntity($em, $entity) + 1);
		}
		return $nextId;
	}

	private function incrementAndSaveNextId(EntityManager $em, NextId $nextId)
	{
		$nextId->increment();
		$em->persist($nextId);
		$em->flush();
	}

	private function getMaxIdForEntity(EntityManager $em, $entity)
	{
		$query = $em->createQuery(sprintf('SELECT MAX(e.id) FROM %s e', get_class($entity)));
		return $query->getSingleScalarResult();
	}
}
