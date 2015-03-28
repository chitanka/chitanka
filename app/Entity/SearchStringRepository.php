<?php namespace App\Entity;

/**
 *
 */
class SearchStringRepository extends EntityRepository {

	/**
	 * @param string $name
	 */
	public function findByName($name) {
		return $this->findOneBy(['name' => $name]);
	}

	/**
	 * @param int $limit
	 */
	public function getLatest($limit = null) {
		$sort = 'e.date DESC';
		return $this->getByIds($this->getIdsBySort($sort, $limit), $sort);
	}

	/**
	 * @param int $limit
	 */
	public function getTop($limit = null) {
		$sort = 'e.count DESC, e.date DESC';
		return $this->getByIds($this->getIdsBySort($sort, $limit), $sort);
	}

	/**
	 * @param string $sort
	 * @param int $limit
	 */
	public function getIdsBySort($sort, $limit = null) {
		$dql = sprintf('SELECT e.id FROM %s e ORDER BY %s', $this->getEntityName(), $sort);
		$query = $this->_em->createQuery($dql)->setMaxResults($limit);

		return $query->getResult('id');
	}

}
