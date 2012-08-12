<?php

namespace Chitanka\LibBundle\Entity;

/**
 *
 */
class CategoryRepository extends EntityRepository
{
	/** @return Category */
	public function findBySlug($slug)
	{
		return $this->findOneBy(array('slug' => $slug));
	}

	/**
	 * RAW_SQL
	 */
	public function getAllAsTree()
	{
		$categories = $this->convertArrayToTree($this->getAll());

		return $categories;
	}

	/**
	 * RAW_SQL
	 */
	public function getAll()
	{
		$categories = $this->_em->getConnection()->fetchAll('SELECT * FROM category ORDER BY name');

		return $categories;
	}

	/**
	 * RAW_SQL
	 */
	public function getRoots()
	{
		$categories = $this->_em->getConnection()->fetchAll('SELECT * FROM category WHERE parent_id IS NULL ORDER BY name');

		return $categories;
	}


	/** TODO move to some utility class */
	protected function convertArrayToTree($labels)
	{
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


	public function getByNames($name)
	{
		return $this->getQueryBuilder()
			->where('e.name LIKE ?1')
			->setParameter(1, "%$name%")
			->getQuery()
			->getArrayResult();
	}

}
