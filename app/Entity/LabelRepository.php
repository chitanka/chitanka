<?php namespace App\Entity;

/**
 *
 */
class LabelRepository extends EntityRepository {
	/**
	 * @param string $slug
	 */
	public function findBySlug($slug) {
		return $this->findOneBy(array('slug' => $slug));
	}

	public function getAll() {
		return $this->getQueryBuilder()
			->orderBy('e.name', 'asc')
			->getQuery()
			->getArrayResult();
	}

	/**
	 * RAW_SQL
	 */
	public function getAllAsTree() {
		$labels = $this->_em->getConnection()->fetchAll('SELECT * FROM label ORDER BY name');
		$labels = $this->convertArrayToTree($labels);

		return $labels;
	}

	protected function convertArrayToTree($labels) {
		$labelsById = array();
		foreach ($labels as $i => $label) {
			$labelsById[ $label['id'] ] =& $labels[$i];
		}

		foreach ($labels as $i => $label) {
			if ($label['parent_id']) {
				$labelsById[$label['parent_id']]['children'][] =& $labels[$i];
			}
		}

		return $labels;
	}

	public function getNames() {
		return $this->_em->createQueryBuilder()
			->from($this->getEntityName(), 'l')->select('l.id, l.name')
			->getQuery()->getResult('key_value');
	}

	public function getByNames($name) {
		return $this->getQueryBuilder()
			->where('e.name LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($name))
			->getQuery()
			->getArrayResult();
	}

}
