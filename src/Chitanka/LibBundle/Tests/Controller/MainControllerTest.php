<?php
namespace Chitanka\LibBundle\Tests\Controller;

class MainControllerTest extends WebTestCase
{
	public function testCatalogAtom()
	{
		$page = $this->request('catalog.atom');

		$this->assertAtomPageIs($page, 'catalog');
		$this->assertCount(1, $page->filter('feed'));
		$this->assertCountGe(6, $page->filter('entry'));
	}
}
