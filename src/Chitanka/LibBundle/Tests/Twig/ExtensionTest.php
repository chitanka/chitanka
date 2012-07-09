<?php
namespace Chitanka\LibBundle\Tests\Twig;

use Chitanka\LibBundle\Tests\TestCase;
use Chitanka\LibBundle\Twig\Extension;

class ExtensionTest extends TestCase
{
	public function testFormatLinksForOneLink()
	{
		$ext = new Extension;
		$formatted = $ext->formatLinks('http://chitanka.info/about');
		$this->assertEquals('<a href="http://chitanka.info/about">chitanka.info</a>', $formatted);
	}

	public function testFormatLinksForTwoLinksWithComma()
	{
		$ext = new Extension;
		$formatted = $ext->formatLinks('http://chitanka.info/, http://chitanka.info/about');
		$this->assertEquals('<a href="http://chitanka.info/">chitanka.info</a>, <a href="http://chitanka.info/about">chitanka.info</a>', $formatted);
	}
}
