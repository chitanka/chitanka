<?php namespace App\Math;

class Combiner {

	public static function combineEveryTwoWithKeys(array $data, array $carryCombinations = []) {
		if (count($data) === 0) {
			return $carryCombinations;
		}
		$key = key($data);
		$value = $data[$key];
		unset($data[$key]);
		$combiner = function($otherKey, $otherValue) use ($key, $value) {
			return [$key => $value, $otherKey => $otherValue];
		};
		return self::combineEveryTwoWithKeys($data, array_merge($carryCombinations, array_map($combiner, array_keys($data), array_values($data))));
	}

	/**
	 * Combine every two members of an array into two-element sets
	 */
	public static function combineIntoSetsOfTwo(array $values, array $carry = []): array {
		if (count($values) === 0) {
			return $carry;
		}
		$value = array_shift($values);
		$combiner = function($value2) use ($value) {
			return [$value, $value2];
		};
		return self::combineIntoSetsOfTwo($values, array_merge($carry, array_map($combiner, $values)));
	}
}
