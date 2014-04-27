<?php namespace App\Tests\Legacy;

use App\Tests\TestCase;
use App\Legacy\SfbParserSimple;

class SfbParserSimpleTest extends TestCase {

	public function testOneLevelHeaders() {
		$headlevel = 1;
		$parser = new SfbParserSimple($this->createTestFile(), $headlevel);
		$parser->convert();
		$expectedHeaders = array(
			array(
				'nr' => 1,
				'level' => 1,
				'title' => 'Глава 1. Началото',
				'file_pos' => 0,
				'line_count' => 9,
			),
			array(
				'nr' => 2,
				'level' => 1,
				'title' => 'Глава 2',
				'file_pos' => 75,
				'line_count' => 1,
			),
		);
		$this->assertEquals($expectedHeaders, $parser->headersFlat());
	}

	public function testTwoLevelHeaders() {
		$headlevel = 2;
		$parser = new SfbParserSimple($this->createTestFile(), $headlevel);
		$parser->convert();
		$expectedHeaders = array(
			array(
				'nr' => 1,
				'level' => 1,
				'title' => 'Глава 1. Началото',
				'file_pos' => 0,
				'line_count' => 6,
			),
			array(
				'nr' => 1,
				'level' => 2,
				'title' => 'Част 1',
				'file_pos' => 0,
				'line_count' => 6,
			),
			array(
				'nr' => 2,
				'level' => 2,
				'title' => 'Част 2',
				'file_pos' => 55,
				'line_count' => 3,
			),
			array(
				'nr' => 3,
				'level' => 1,
				'title' => 'Глава 2',
				'file_pos' => 75,
				'line_count' => 1,
			),
		);
		$this->assertEquals($expectedHeaders, $parser->headersFlat());
	}

	private function createTestFile() {
		$c = <<<SFB
>	Глава 1
>	Началото

>>	Част 1
	xxx

>>	Част 2
	xxx

>	Глава 2
	xxx
SFB;
		$filename = sys_get_temp_dir().'/'.uniqid('chitanka-test-', true);
		file_put_contents($filename, $c);
		return $filename;
	}
}
