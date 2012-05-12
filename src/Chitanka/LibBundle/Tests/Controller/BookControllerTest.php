<?php
namespace Chitanka\LibBundle\Tests\Controller;

class BookControllerTest extends WebTestCase
{
	public function testIndex()
	{
		$page = $this->request('books');

		$this->assertHtmlPageIs($page, 'books');
		$this->assertCount(1, $page->filter('h1'));
	}

	public function testListByAlphaByLetterA()
	{
		$page = $this->request("books/alpha/".urlencode('А'));

		$this->assertHtmlPageIs($page, 'books_by_alpha');
	}

	public function testIndexOpds()
	{
		$route = "books.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(2, $page->filter('entry'));
	}

	public function testIndexByAlphaOpds()
	{
		$route = "books/alpha.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(30, $page->filter('entry'));
	}

	public function testIndexByCategoryOpds()
	{
		$route = "books/category.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

	public function testListByAlphaLetterAOpds()
	{
		$route = "books/alpha/".urlencode('А').".opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

	public function testListByCategoryFantastikaOpds()
	{
		$route = "books/category/fantastika.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

}
