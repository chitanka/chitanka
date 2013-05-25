<?php
namespace Chitanka\LibBundle\Tests\Entity\Content;

use Chitanka\LibBundle\Tests\TestCase;
use Chitanka\LibBundle\Entity\Content\BookTemplate;

class BookTemplateTest extends TestCase {

	public function testExtractTextIds()
	{
		$template = <<<TPL
>	{text:123}

>	{text:456-part1}

	{file:789}
TPL;
		$ids = BookTemplate::extractTextIds($template);
		$expectedIds = array(
			'123',
			'456',
			'789',
		);
		$this->assertEquals($expectedIds, $ids);
	}

}
