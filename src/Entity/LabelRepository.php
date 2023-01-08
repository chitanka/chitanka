<?php namespace App\Entity;

/**
 *
 */
class LabelRepository extends EntityRepository {
	/**
	 * @param string $slug
	 * @return Label
	 */
	public function findBySlug($slug) {
		return $this->findOneBy(['slug' => $slug]);
	}

	/**
	 * @return Label[]
	 */
	public function findAll() {
		return $this->findBy([], ['name' => 'asc']);
	}

	public function getAll($group = null) {
		$qb = $this->getQueryBuilder()
			->addSelect('IDENTITY(e.parent) AS parent')
			->orderBy('e.position')->addOrderBy('e.name');
		if ($group) {
			$qb->where('e.group = ?1')->setParameter(1, $group);
		}
		$labelResult = $qb->getQuery()->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)->getArrayResult();
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
				unset($labels[$i]);
			}
		}
		return array_values($labels);
	}

	public function getNames() {
		return $this->_em->createQueryBuilder()
			->from($this->getEntityName(), 'l')->select('l.id, l.name')
			->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getResult('key_value');
	}

	/**
	 * @param string $name
	 */
	public function getByNames($name) {
		return $this->getQueryBuilder()
			->where('e.name LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($name))
			->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getArrayResult();
	}

	/**
	 * @param Label $label
	 * @return Label[]
	 */
	public function findLabelAncestors(Label $label) {
		return $this->fetchFromCache('LabelAncestors_'.$label->getId(), function() use ($label) {
			return $label->getAncestors();
		});
	}

	/**
	 * @param Label $label
	 * @return array Array of label IDs
	 */
	public function getLabelDescendantIdsWithSelf(Label $label) {
		return $this->fetchFromCache('LabelDescendantIdsWithSelf_'.$label->getId(), function() use ($label) {
			return array_merge([$label->getId()], $label->getDescendantIds());
		});
	}

}
