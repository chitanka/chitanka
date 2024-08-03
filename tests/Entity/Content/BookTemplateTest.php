<?php namespace App\Tests\Entity\Content;

use App\Entity\Content\BookTemplate;
use App\Tests\TestCase;

class BookTemplateTest extends TestCase {

	public function testExtractTextIds() {
		$template = <<<TPL
>	{text:123}

>	{text:456-part1}

	{file:789}
TPL;
		$ids = BookTemplate::extractTextIds($template);
		$expectedIds = [
			'123',
			'456',
			'789',
		];
		$this->assertEquals($expectedIds, $ids);
	}

}
