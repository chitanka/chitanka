<?php
namespace Chitanka\LibBundle\Tests\Controller;

class PersonControllerTest extends WebTestCase
{
	protected $routeBase = '';

	public function testIndex()
	{
		$page = $this->request($this->routeBase);

		$this->assertHtmlPageIs($page, $this->routeBase);
		$this->assertCount(1, $page->filter('h1'));
		$this->assertCountGe(2, $page->filter('h2'));
	}

	public function testListByFirstNameByLetterA()
	{
		$page = $this->request("$this->routeBase/first-name/".urlencode('А'));

		$this->assertHtmlPageIs($page, $this->routeBase.'_by_alpha');
	}

	public function testListByLastNameByLetterA()
	{
		$page = $this->request("$this->routeBase/last-name/".urlencode('А'));

		$this->assertHtmlPageIs($page, $this->routeBase.'_by_alpha');
	}

	public function testIndexOpds()
	{
		$page = $this->request("$this->routeBase.opds");

		$this->assertOpdsPageIs($page, $this->routeBase);
		$this->assertCountGe(2, $page->filter('entry'));
	}

	public function testIndexByFirstNameOpds()
	{
		$this->doTestIndexByAlphaOpds('first-name');
	}

	public function testIndexByLastNameOpds()
	{
		$this->doTestIndexByAlphaOpds('last-name');
	}

	public function doTestIndexByAlphaOpds($by)
	{
		$route = "$this->routeBase/$by.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(30, $page->filter('entry'));
	}

	public function testListByAlphaByFirstNameByLetterAOpds()
	{
		$this->doTestListByAlphaByLetterOpds('first-name', urlencode('А'));
	}

	public function testListByAlphaByLastNameByLetterAOpds()
	{
		$this->doTestListByAlphaByLetterOpds('last-name', urlencode('А'));
	}

	public function doTestListByAlphaByLetterOpds($by, $letter)
	{
		$route = "$this->routeBase/$by/$letter.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
	}

}
