<?php namespace App\Persistence;

use App\Entity\NextId;

/**
 *
 */
class NextIdRepository extends \Doctrine\ORM\EntityRepository {

	/**
	 * @param object $entity
	 * @return int
	 */
	public function selectNextId($entity) {
		$nextId = $this->findNextId(get_class($entity));
		$entityId = $nextId->getValue();
		$this->incrementAndSaveNextId($nextId);

		return $entityId;
	}

	/**
	 * @param string $entityName
	 * @return NextId
	 */
	public function findNextId($entityName) {
		$nextId = $this->find($entityName);
		if ($nextId === null) {
			$nextId = new NextId($entityName);
			$nextId->setValue($this->getMaxIdForEntity($entityName) + 1);
		}
		return $nextId;
	}

	/**
	 * @param NextId $nextId
	 */
	private function incrementAndSaveNextId(NextId $nextId) {
		$nextId->increment();
		$sql = sprintf("REPLACE next_id SET id = '%s', value = %d", addslashes($nextId->getId()), $nextId->getValue());
		$this->getEntityManager()->getConnection()->executeUpdate($sql);
	}

	/**
	 * @param string $entityName
	 * @return int
	 */
	private function getMaxIdForEntity($entityName) {
		$query = $this->getEntityManager()->createQuery(sprintf('SELECT MAX(e.id) FROM %s e', $entityName));
		return $query->getSingleScalarResult();
	}

}
