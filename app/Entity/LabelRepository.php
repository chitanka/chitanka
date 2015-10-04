<?php namespace App\Entity;

/**
 *
 */
class LabelRepository extends EntityRepository {
	/**
	 * @param string $slug
	 */
	public function findBySlug($slug) {
		return $this->findOneBy(['slug' => $slug]);
	}

	public function getAll($group = null) {
		$qb = $this->getQueryBuilder()
			->addSelect('IDENTITY(e.parent) AS parent')
			->orderBy('e.name');
		if ($group) {
			$qb->where('e.group = ?1')->setParameter(1, $group);
		}
		$labelResult = $qb->getQuery()->getArrayResult();
		foreach ($labelResult as $k => $row) {
			$labelResult[$k] += $row[0];
			unset($labelResult[$k][0]);
		}
		return $labelResult;
	}

	/**
	 * Return all labels by group and ordered as a tree
	 * @return array
	 */
	public function getAllAsTree() {
		$labels = [];
		foreach (Label::getAvailableGroups() as $group) {
			$labels[$group] = $this->convertArrayToTree($this->getAll($group));
		}
		return $labels;
	}

	protected function convertArrayToTree($labels) {
		$labelsById = [];
		foreach ($labels as $i => $label) {
			$labelsById[ $label['id'] ] =& $labels[$i];
		}

		foreach ($labels as $i => $label) {
			if ($label['parent']) {
				$labelsById[$label['parent']]['children'][] =& $labels[$i];
			}
		}

		return $labels;
	}

	public function getNames() {
		return $this->_em->createQueryBuilder()
			->from($this->getEntityName(), 'l')->select('l.id, l.name')
			->getQuery()->getResult('key_value');
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
