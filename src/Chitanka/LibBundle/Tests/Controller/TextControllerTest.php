<?php
namespace Chitanka\LibBundle\Tests\Controller;

class TextControllerTest extends WebTestCase
{
	/**
	 * @group html
	 */
	public function testIndex()
	{
		$page = $this->request('texts');

		$this->assertHtmlPageIs($page, 'texts');
		$this->assertCount(1, $page->filter('h1'));
	}

	/**
	 * @group opds
	 */
	public function testIndexOpds()
	{
		$route = "texts.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(3, $page->filter('entry'));
	}

	/**
	 * @group opds
	 */
	public function testIndexByAlphaOpds()
	{
		$route = "texts/alpha.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(30, $page->filter('entry'));
	}

	/**
	 * @group opds
	 */
	public function testIndexByLabelOpds()
	{
		$route = "texts/label.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

	/**
	 * @group opds
	 */
	public function testIndexByTypeOpds()
	{
		$route = "texts/type.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

	/**
	 * @group opds
	 */
	public function testListByAlphaByLetterAOpds()
	{
		$route = "texts/alpha/".urlencode('А').".opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

	/**
	 * @group opds
	 */
	public function testListByLabelByAuthorOpds()
	{
		$route = "texts/label/by-author.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

	/**
	 * @group opds
	 */
	public function testListByTypeByNovelOpds()
	{
		$route = "texts/type/novel.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

}
