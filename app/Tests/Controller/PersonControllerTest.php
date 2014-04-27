<?php namespace App\Tests\Controller;

class PersonControllerTest extends WebTestCase {
	protected $routeBase = '';

	/**
	 * @group html
	 */
	public function testIndex() {
		$page = $this->request($this->routeBase);

		$this->assertHtmlPageIs($page, $this->routeBase);
		$this->assertCount(1, $page->filter('h1'));
		$this->assertCountGe(2, $page->filter('h2'));
		$this->assertEquals("/$this->routeBase/first-name/".urlencode('А'), $page->filter('div.first-name a')->eq(0)->attr("href"), 'First link from the first-name navigation');
		$this->assertEquals("/$this->routeBase/last-name/".urlencode('А'), $page->filter('div.last-name a')->eq(0)->attr("href"), 'First link from the last-name navigation');
	}

	/**
	 * @group html
	 */
	public function testListByFirstNameByLetterA() {
		$page = $this->request("$this->routeBase/first-name/".urlencode('А'));

		$this->assertHtmlPageIs($page, $this->routeBase.'_by_alpha');
	}

	/**
	 * @group html
	 */
	public function testListByLastNameByLetterA() {
		$page = $this->request("$this->routeBase/last-name/".urlencode('А'));

		$this->assertHtmlPageIs($page, $this->routeBase.'_by_alpha');
	}

	/**
	 * @group html
	 */
	public function testShow() {
		$page = $this->request("person/nikolaj-tellalov");

		$this->assertHtmlPageIs($page, 'person_show');
	}

	/**
	 * @group opds
	 */
	public function testIndexOpds() {
		$page = $this->request("$this->routeBase.opds");

		$this->assertOpdsPageIs($page, $this->routeBase);
		$this->assertCountGe(2, $page->filter('entry'));
	}

	/**
	 * @group opds
	 */
	public function testIndexByFirstNameOpds() {
		$this->doTestIndexByAlphaOpds('first-name');
	}
	/**
	 * @group opds
	 */
	public function testIndexByLastNameOpds() {
		$this->doTestIndexByAlphaOpds('last-name');
	}
	private function doTestIndexByAlphaOpds($by) {
		$route = "$this->routeBase/$by.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(30, $page->filter('entry'));
	}

	/**
	 * @group opds
	 */
	public function testListByAlphaByFirstNameByLetterAOpds() {
		$this->doTestListByAlphaByLetterOpds('first-name', urlencode('А'));
	}
	/**
	 * @group opds
	 */
	public function testListByAlphaByLastNameByLetterAOpds() {
		$this->doTestListByAlphaByLetterOpds('last-name', urlencode('А'));
	}
	private function doTestListByAlphaByLetterOpds($by, $letter) {
		$route = "$this->routeBase/$by/$letter.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
	}

	/**
	 * @group opds
	 */
	public function testShowOpds() {
		$route = "person/nikolaj-tellalov.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

}
