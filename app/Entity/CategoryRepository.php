<?php namespace App\Entity;

/**
 *
 */
class CategoryRepository extends EntityRepository {
	/** @param string $slug
/** @return Category */
	public function findBySlug($slug) {
		return $this->findOneBy(array('slug' => $slug));
	}

	/**
	 * RAW_SQL
	 */
	public function getAllAsTree() {
		$categories = $this->convertArrayToTree($this->getAll());

		return $categories;
	}

	/**
	 * RAW_SQL
	 */
	public function getAll() {
		$categories = $this->_em->getConnection()->fetchAll('SELECT * FROM category ORDER BY name');

		return $categories;
	}

	/**
	 * RAW_SQL
	 */
	public function getRoots() {
		$categories = $this->_em->getConnection()->fetchAll('SELECT * FROM category WHERE parent_id IS NULL ORDER BY name');

		return $categories;
	}

	/** TODO move to some utility class */
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

	/**
	 * @param string $name
	 */
	public function getByNames($name) {
		return $this->getQueryBuilder()
			->where('e.name LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($name))
			->getQuery()
			->getArrayResult();
	}

}
