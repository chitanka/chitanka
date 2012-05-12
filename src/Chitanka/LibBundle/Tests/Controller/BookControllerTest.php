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

	public function testIndexAtom()
	{
		$route = "books.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
		$this->assertCountGe(2, $page->filter('entry'));
	}

	public function testIndexByAlphaAtom()
	{
		$route = "books/alpha.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
		$this->assertCountGe(30, $page->filter('entry'));
	}

	public function testIndexByCategoryAtom()
	{
		$route = "books/category.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

	public function testListByAlphaLetterAAtom()
	{
		$route = "books/alpha/".urlencode('А').".atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

	public function testListByCategoryFantastikaAtom()
	{
		$route = "books/category/fantastika.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

}
