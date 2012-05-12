<?php
namespace Chitanka\LibBundle\Tests\Controller;

class AuthorControllerTest extends PersonControllerTest
{
	protected $routeBase = 'authors';

	public function testIndexByCountryByFirstName()
	{
		$this->doTestIndexByCountry('first-name');
	}
	public function testIndexByCountryByLastName()
	{
		$this->doTestIndexByCountry('last-name');
	}
	private function doTestIndexByCountry($by)
	{
		$page = $this->request("$this->routeBase/country/$by");

		$this->assertHtmlPageIs($page, $this->routeBase.'_by_country_index');
	}

	public function testListByCountryByFirstName()
	{
		$this->doTestListByCountry('first-name');
	}
	public function testListByCountryByLastName()
	{
		$this->doTestListByCountry('last-name');
	}
	private function doTestListByCountry($by)
	{
		$page = $this->request("$this->routeBase/country/bg/$by");

		$this->assertHtmlPageIs($page, $this->routeBase.'_by_country');
	}

	public function testIndexByCountryByFirstNameOpds()
	{
		$this->doTestIndexByCountryOpds('first-name');
	}
	public function testIndexByCountryByLastNameOpds()
	{
		$this->doTestIndexByCountryOpds('last-name');
	}
	private function doTestIndexByCountryOpds($by)
	{
		$route = "$this->routeBase/country/$by.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
	}

	public function testListByCountryByFirstNameOpds()
	{
		$this->doTestListByCountryOpds('first-name');
	}
	public function testListByCountryByLastNameOpds()
	{
		$this->doTestListByCountryOpds('last-name');
	}
	private function doTestListByCountryOpds($by)
	{
		$route = "$this->routeBase/country/bg/$by.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
	}

	public function testShowOpds()
	{
		$route = "person/roger-zelazny.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

}
