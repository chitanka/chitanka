<?php namespace App\Tests\Util;

use App\Tests\TestCase;
use App\Util\Stringy;

class StringyTest extends TestCase {

	/**
	 * @dataProvider data_createAcronym
	 */
	public function test_createAcronym($input, $expected) {
		$this->assertSame($expected, Stringy::createAcronym($input));
	}
	public function data_createAcronym() {
		return [
			['', ''],
			['Колелото на времето', 'КНВ'],
			['Wheel of Time', 'WoT'],
			['„Серията“', 'С'],
			['123', '1'],
			['äöü', ''],
		];
	}
}
