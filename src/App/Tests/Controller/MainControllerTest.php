<?php namespace App\Tests\Controller;

class MainControllerTest extends WebTestCase {
	/**
	 * @group html
	 */
	public function testIndex() {
		$page = $this->request('');

		$this->assertHtmlPageIs($page, 'homepage');
		$this->assertCountGe(1, $page->filter('h1'));
	}

	/**
	 * @group opds
	 */
	public function testCatalogOpds() {
		$page = $this->request('catalog.opds');

		$this->assertOpdsPageIs($page, 'catalog');
		$this->assertCount(1, $page->filter('feed'));
		$this->assertCountGe(6, $page->filter('entry'));
	}
}
