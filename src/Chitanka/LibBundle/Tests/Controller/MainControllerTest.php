<?php
namespace Chitanka\LibBundle\Tests\Controller;

class MainControllerTest extends WebTestCase
{
	public function testCatalogOpds()
	{
		$page = $this->request('catalog.opds');

		$this->assertOpdsPageIs($page, 'catalog');
		$this->assertCount(1, $page->filter('feed'));
		$this->assertCountGe(6, $page->filter('entry'));
	}
}
