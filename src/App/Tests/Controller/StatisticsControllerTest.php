<?php namespace App\Tests\Controller;

class StatisticsControllerTest extends WebTestCase {
	/**
	 * @group html
	 */
	public function testIndex() {
		$page = $this->request('statistics');
		$this->assertHtmlPageIs($page, 'statistics');
		$this->assertCountGe(3, $page->filter('table'));
	}
}
