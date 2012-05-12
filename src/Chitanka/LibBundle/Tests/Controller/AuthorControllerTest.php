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

	public function testIndexByCountryByFirstNameAtom()
	{
		$this->doTestIndexByCountryAtom('first-name');
	}
	public function testIndexByCountryByLastNameAtom()
	{
		$this->doTestIndexByCountryAtom('last-name');
	}
	private function doTestIndexByCountryAtom($by)
	{
		$route = "$this->routeBase/country/$by.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
	}

	public function testListByCountryByFirstNameAtom()
	{
		$this->doTestListByCountryAtom('first-name');
	}
	public function testListByCountryByLastNameAtom()
	{
		$this->doTestListByCountryAtom('last-name');
	}
	private function doTestListByCountryAtom($by)
	{
		$route = "$this->routeBase/country/bg/$by.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
	}

	public function testShowAtom()
	{
		$route = "person/roger-zelazny.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

}
