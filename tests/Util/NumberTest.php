<?php namespace App\Tests\Util;

use App\Tests\TestCase;
use App\Util\Number;

class NumberTest extends TestCase {

	/**
	 * @dataProvider iniBytesProvider
	 */
	public function testIniBytes($input, $expected) {
		$this->assertEquals($expected, Number::iniBytes($input));
	}

	public function iniBytesProvider() {
		return [
			['20K', 20480],
			['20M', 20971520],
			['1G', 1073741824],
		];
	}
}
