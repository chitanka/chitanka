<?php
namespace Chitanka\LibBundle\Tests\Controller;

class SeriesControllerTest extends WebTestCase
{

	/**
	 * @group html
	 */
	public function testIndex()
	{
		$page = $this->request('series');

		$this->assertHtmlPageIs($page, 'series');
		$this->assertCount(1, $page->filter('h1'));
	}

	/**
	 * @group html
	 */
	public function testListByAlphaByLetterA()
	{
		$page = $this->request("series/alpha/".urlencode('А'));

		$this->assertHtmlPageIs($page, 'series_by_alpha');
	}

	/**
	 * @group html
	 */
	public function testShow()
	{
		$series = 'hronikite-na-ambyr';
		$page = $this->request("serie/$series");

		$this->assertHtmlPageIs($page, 'series_show');
	}

	/**
	 * @group opds
	 */
	public function testIndexOpds()
	{
		$page = $this->request("series.opds");

		$this->assertOpdsPageIs($page, 'series');
		$this->assertCountGe(1, $page->filter('entry'));
	}

	/**
	 * @group opds
	 */
	public function testListByAlphaByLetterAOpds()
	{
		$route = "series/alpha/".urlencode('А').".opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
	}

	/**
	 * @group opds
	 */
	public function testShowOpds()
	{
		$series = 'hronikite-na-ambyr';
		$route = "serie/$series.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
	}

}
