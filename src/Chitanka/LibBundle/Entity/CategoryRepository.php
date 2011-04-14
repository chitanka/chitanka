<?php

namespace Chitanka\LibBundle\Entity;

class CategoryRepository extends EntityRepository
{
	public function findBySlug($slug)
	{
		return $this->findOneBy(array('slug' => $slug));
	}

	/**
	* @RawSql
	*/
	public function getAllAsTree()
	{
		$labels = $this->_em->getConnection()->fetchAll('SELECT * FROM category ORDER BY name');
		$labels = $this->convertArrayToTree($labels);

		return $labels;
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

}
