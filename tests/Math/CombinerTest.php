<?php namespace App\Tests\Math;

use App\Math\Combiner;

class CombinerTest extends \App\Tests\TestCase {

	/** @dataProvider data_combineEveryTwoWithKeys */
	public function test_combineEveryTwoWithKeys(array $input, array $expected) {
		$result = Combiner::combineEveryTwoWithKeys($input);
		$this->assertSame($expected, $result);
	}

	public function data_combineEveryTwoWithKeys() {
		return [
			'empty' => [ [], [] ],
			'one combination' => [
				['A' => 'a', 'B' => 'b'],
				[
					['A' => 'a', 'B' => 'b'],
				]
			],
			'three combinations' => [
				['A' => 'a', 'B' => 'b', 'C' => 'c'],
				[
					['A' => 'a', 'B' => 'b'],
					['A' => 'a', 'C' => 'c'],
					['B' => 'b', 'C' => 'c'],
				]
			],
			'six combinations' => [
				['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd'],
				[
					['A' => 'a', 'B' => 'b'],
					['A' => 'a', 'C' => 'c'],
					['A' => 'a', 'D' => 'd'],
					['B' => 'b', 'C' => 'c'],
					['B' => 'b', 'D' => 'd'],
					['C' => 'c', 'D' => 'd'],
				]
			],
		];
	}

	/** @dataProvider data_combineIntoSetsOfTwo */
	public function test_combineIntoSetsOfTwo(array $input, array $expected) {
		$result = Combiner::combineIntoSetsOfTwo($input);
		$this->assertSame($expected, $result);
	}

	public function data_combineIntoSetsOfTwo() {
		return [
			'empty' => [ [], [] ],
			'2 elements' => [
				['a', 'b'],
				[ ['a', 'b'] ]
			],
			'3 elements' => [
				['a', 'b', 'c'],
				[
					['a', 'b'],
					['a', 'c'],
					['b', 'c'],
				]
			],
			'4 elements' => [
				['a', 'b', 'c', 'd'],
				[
					['a', 'b'],
					['a', 'c'],
					['a', 'd'],
					['b', 'c'],
					['b', 'd'],
					['c', 'd'],
				]
			],
		];
	}
}
