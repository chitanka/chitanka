<?php namespace App\Entity\Query;

use App\Persistence\EntityRepository;

class SortingItem {

	const ENTITY_ALIAS = EntityRepository::ALIAS;
	const ALIAS_NAME_SEPARATOR = '.';
	const FIELD_DIRECTION_SEPARATOR = ' ';
	const FIELD_DIRECTION_SEPARATOR2 = '-';

	public $field;
	public $direction;

	public function __construct(string $input, string $entityAlias = null) {
		$input = str_replace(self::FIELD_DIRECTION_SEPARATOR2, self::FIELD_DIRECTION_SEPARATOR, trim($input));
		$parts = array_map('trim', explode(self::FIELD_DIRECTION_SEPARATOR, $input));
		$this->field = $this->sanitizeField($parts[0]);
		if (strpos($this->field, self::ALIAS_NAME_SEPARATOR) === false) {
			$this->field = ($entityAlias ?? self::ENTITY_ALIAS) . self::ALIAS_NAME_SEPARATOR . $this->field;
		}
		$this->direction = $this->sanitizeDirection($parts[1] ?? '');
	}

	public function __toString() {
		return rtrim("$this->field $this->direction");
	}

	public function fieldWoAlias(): string {
		return explode(self::ALIAS_NAME_SEPARATOR, $this->field)[1];
	}

	private function sanitizeField(string $rawField): string {
		return preg_replace('/[^a-zA-Z\d_]\./', '', $rawField);
	}

	private function sanitizeDirection(string $rawDirection): string {
		if ( ! in_array(strtolower($rawDirection), ['asc', 'desc'])) {
			return 'asc';
		}
		return $rawDirection;
	}
}
