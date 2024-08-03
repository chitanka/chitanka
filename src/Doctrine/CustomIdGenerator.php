<?php namespace App\Doctrine;

use App\Persistence\NextIdRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;

class CustomIdGenerator extends AbstractIdGenerator {

	private $nextIdRepository;

	public function __construct(NextIdRepository $nextIdRepository) {
		$this->nextIdRepository = $nextIdRepository;
	}

	public function generateId(EntityManagerInterface $em, $entity) {
		return $this->nextIdRepository->selectNextId($entity);
	}

}
