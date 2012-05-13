<?php
namespace Chitanka\LibBundle\Tests\Controller;

class AuthorControllerTest extends PersonControllerTest
{
	protected $routeBase = 'authors';

	/**
	 * @group html
	 */
	public function testIndexByCountryByFirstName()
	{
		$this->doTestIndexByCountry('first-name');
	}
	/**
	 * @group html
	 */
	public function testIndexByCountryByLastName()
	{
		$this->doTestIndexByCountry('last-name');
	}
	private function doTestIndexByCountry($by)
	{
		$page = $this->request("$this->routeBase/country/$by");

		$this->assertHtmlPageIs($page, $this->routeBase.'_by_country_index');
	}

	/**
	 * @group html
	 */
	public function testListByCountryByFirstName()
	{
		$this->doTestListByCountry('first-name');
	}
	/**
	 * @group html
	 */
	public function testListByCountryByLastName()
	{
		$this->doTestListByCountry('last-name');
	}
	private function doTestListByCountry($by)
	{
		$page = $this->request("$this->routeBase/country/bg/$by");

		$this->assertHtmlPageIs($page, $this->routeBase.'_by_country');
	}

	/**
	 * @group html
	 */
	public function testShow()
	{
		$page = $this->request("author/nikolaj-tellalov");

		$this->assertHtmlPageIs($page, 'author_show');
	}

	/**
	 * @group html
	 */
	public function testShowBooks()
	{
		$page = $this->request("author/nikolaj-tellalov/books");

		$this->assertHtmlPageIs($page, 'author_show_books');
	}

	/**
	 * @group html
	 */
	public function testShowTexts()
	{
		$page = $this->request("author/nikolaj-tellalov/texts");

		$this->assertHtmlPageIs($page, 'author_show_texts');
	}

	/**
	 * @group opds
	 */
	public function testIndexByCountryByFirstNameOpds()
	{
		$this->doTestIndexByCountryOpds('first-name');
	}
	/**
	 * @group opds
	 */
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

	/**
	 * @group opds
	 */
	public function testListByCountryByFirstNameOpds()
	{
		$this->doTestListByCountryOpds('first-name');
	}
	/**
	 * @group opds
	 */
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

	/**
	 * @group opds
	 */
	public function testShowOpds()
	{
		$route = "author/nikolaj-tellalov.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

	/**
	 * @group opds
	 */
	public function testShowBooksOpds()
	{
		$route = "author/nikolaj-tellalov/books.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

	/**
	 * @group opds
	 */
	public function testShowTextsOpds()
	{
		$route = "author/nikolaj-tellalov/texts.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

}
