<?php
namespace Chitanka\LibBundle\Tests\Controller;

class SeriesControllerTest extends WebTestCase
{

	public function testIndex()
	{
		$page = $this->request('series');

		$this->assertHtmlPageIs($page, 'series');
		$this->assertCount(1, $page->filter('h1'));
	}

	public function testListByAlphaByLetterA()
	{
		$page = $this->request("series/alpha/".urlencode('А'));

		$this->assertHtmlPageIs($page, 'series_by_alpha');
	}

	public function testShow()
	{
		$series = 'hronikite-na-ambyr';
		$page = $this->request("serie/$series");

		$this->assertHtmlPageIs($page, 'series_show');
	}

	public function testIndexAtom()
	{
		$page = $this->request("series.atom");

		$this->assertAtomPageIs($page, 'series');
		$this->assertCountGe(1, $page->filter('entry'));
	}

	public function testListByAlphaByLetterAAtom()
	{
		$route = "series/alpha/".urlencode('А').".atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
	}

	public function testShowAtom()
	{
		$series = 'hronikite-na-ambyr';
		$route = "serie/$series.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
	}

}
