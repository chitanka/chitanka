<?php namespace App\Entity\Query;

use Doctrine\ORM\QueryBuilder;

class SortingDefinition {

	const FIELDS_SEPARATOR = ',';

	public $input;
	public $sortableFields = [];
	/** @var SortingItem[] */
	public $items = [];

	public function __construct(?string $input, string $entityAlias = null, array $sortableFields = []) {
		$this->input = $input;
		$this->sortableFields = $sortableFields;

		foreach (array_filter(explode(self::FIELDS_SEPARATOR, $input)) as $fieldWithDirection) {
			$this->items[] = new SortingItem($fieldWithDirection, $entityAlias);
		}
		if ($this->sortableFields) {
			$this->items = array_values(array_filter($this->items, function(SortingItem $item) {
				return !in_array($item->field, $this->sortableFields);
			}));
		}
	}

	public function __toString() {
		return implode(', ', $this->items);
	}

	public function equals(string $input): bool {
		return $this->input === $input;
	}

	public function isActive(): bool {
		return count($this->items) > 0;
	}

	public function addToQueryBuilder(QueryBuilder $qb) {
		foreach ($this->items as $item) {
			$qb->addOrderBy($item->field, $item->direction);
		}
	}
}
