<?php
namespace App\Entity;

class NextIdRepository extends \Doctrine\ORM\EntityRepository
{
	public function selectNextId($entity)
	{
		$nextId = $this->findNextId(get_class($entity));
		$entityId = $nextId->getValue();
		$this->incrementAndSaveNextId($nextId);

		return $entityId;
	}

	/** @return NextId */
	public function findNextId($entityName)
	{
		$nextId = $this->find($entityName);
		if ($nextId == false) {
			$nextId = new NextId($entityName);
			$nextId->setValue($this->getMaxIdForEntity($entityName) + 1);
		}
		return $nextId;
	}

	private function incrementAndSaveNextId(NextId $nextId)
	{
		$nextId->increment();
		$sql = sprintf("REPLACE next_id SET id = '%s', value = %d", addslashes($nextId->getId()), $nextId->getValue());
		$this->getEntityManager()->getConnection()->executeUpdate($sql);
	}

	private function getMaxIdForEntity($entityName)
	{
		$query = $this->getEntityManager()->createQuery(sprintf('SELECT MAX(e.id) FROM %s e', $entityName));
		return $query->getSingleScalarResult();
	}

}
