<?php namespace App\Persistence;

use App\Entity\Category;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 */
class CategoryRepository extends EntityRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, CategoryRepository::class);
	}

	/**
	 * @param string $slug
	 * @return Category
	 */
	public function findBySlug($slug) {
		return $this->findOneBy(['slug' => $slug]);
	}

	/**
	 * RAW_SQL
	 */
	public function getAllAsTree() {
		$categories = $this->convertArrayToTree($this->getAll());

		return $categories;
	}

	/**
	 * @return array
	 */
	public function getAll() {
		$categoryResult = $this->getQueryBuilder()
			->addSelect('IDENTITY(e.parent) AS parent')
			->orderBy('e.name')
			->getQuery()
			->useResultCache(true, static::DEFAULT_CACHE_LIFETIME)
			->getArrayResult();
		foreach ($categoryResult as $k => $row) {
			$categoryResult[$k] += $row[0];
			if ($categoryResult[$k]['parent']) {
				$categoryResult[$k]['parent'] = (int) $categoryResult[$k]['parent'];
			}
			unset($categoryResult[$k][0]);
		}

		return $categoryResult;
	}

	/**
	 * TODO move to some utility class
	 * @return array
	 */
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

	/**
	 * @param string $name
	 * @return array
	 */
	public function getByNames($name) {
		return $this->getQueryBuilder()
			->where('e.name LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($name))
			->getQuery()
			->getArrayResult();
	}

	/**
	 * @param Category $category
	 * @return Category[]
	 */
	public function findCategoryAncestors(Category $category) {
		return $this->fetchFromCache('CategoryAncestors_'.$category->getId(), function() use ($category) {
			return $category->getAncestors();
		});
	}

	/**
	 * @param Category $category
	 * @return array Array of category IDs
	 */
	public function getCategoryDescendantIdsWithSelf(Category $category) {
		return $this->fetchFromCache('CategoryDescendantIdsWithSelf_'.$category->getId(), function() use ($category) {
			return array_merge([$category->getId()], $category->getDescendantIds());
		});
	}
}
