<?php
namespace Chitanka\LibBundle\Tests\Controller;

class TextControllerTest extends WebTestCase
{
	public function testIndex()
	{
		$page = $this->request('texts');

		$this->assertHtmlPageIs($page, 'texts');
		$this->assertCount(1, $page->filter('h1'));
	}

	public function testIndexAtom()
	{
		$route = "texts.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
		$this->assertCountGe(3, $page->filter('entry'));
	}

	public function testIndexByAlphaAtom()
	{
		$route = "texts/alpha.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
		$this->assertCountGe(30, $page->filter('entry'));
	}

	public function testIndexByLabelAtom()
	{
		$route = "texts/label.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

	public function testIndexByTypeAtom()
	{
		$route = "texts/type.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

	public function testListByAlphaByLetterAAtom()
	{
		$route = "texts/alpha/".urlencode('А').".atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

	public function testListByLabelByAuthorAtom()
	{
		$route = "texts/label/by-author.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

	public function testListByTypeByNovelAtom()
	{
		$route = "texts/type/novel.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

}
