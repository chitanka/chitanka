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

	public function testIndexAtom()
	{
		$page = $this->request("$this->routeBase.atom");

		$this->assertAtomPageIs($page, $this->routeBase);
		$this->assertCountGe(2, $page->filter('entry'));
	}

	public function testIndexByFirstNameAtom()
	{
		$this->doTestIndexByAlphaAtom('first-name');
	}

	public function testIndexByLastNameAtom()
	{
		$this->doTestIndexByAlphaAtom('last-name');
	}

	public function doTestIndexByAlphaAtom($by)
	{
		$route = "$this->routeBase/$by.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
		$this->assertCountGe(30, $page->filter('entry'));
	}

	public function testListByAlphaByFirstNameByLetterAAtom()
	{
		$this->doTestListByAlphaByLetterAtom('first-name', urlencode('А'));
	}

	public function testListByAlphaByLastNameByLetterAAtom()
	{
		$this->doTestListByAlphaByLetterAtom('last-name', urlencode('А'));
	}

	public function doTestListByAlphaByLetterAtom($by, $letter)
	{
		$route = "$this->routeBase/$by/$letter.atom";
		$page = $this->request($route);

		$this->assertAtomPageIs($page, $route);
	}

}
