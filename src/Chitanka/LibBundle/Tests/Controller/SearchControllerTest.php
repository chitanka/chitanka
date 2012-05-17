<?php
namespace Chitanka\LibBundle\Tests\Controller;

class SearchControllerTest extends WebTestCase
{
	/**
	 * @group html
	 */
	public function testIndex()
	{
		$page = $this->request('search', array('q' => 'test'));

		$this->assertHtmlPageIs($page, 'search');
		$this->assertCount(1, $page->filter('h1'));
	}

	/**
	 * @group xml
	 */
	public function testIndexInXml()
	{
		$query = 'Фантастика';
		$page = $this->request('search.xml', array('q' => $query));

		$this->assertXmlSearchPageIsFor($page, $query);
		$this->assertCount(1, $page->filter('results'));
	}

	/**
	 * @group xml
	 */
	public function testIndexWithEmptyQueryInXml()
	{
		$page = $this->request('search.xml');

		$this->assertCount(1, $page->filter('queries'));
		$this->assertCount(1, $page->filter('top'));
		$this->assertCount(1, $page->filter('latest'));
	}

	/**
	 * @group xml
	 */
	public function testPersonsByNameInXml()
	{
		$query = 'Николай Теллалов';
		$page = $this->request('persons/search.xml', array('q' => $query));

		$this->assertXmlSearchPageIsFor($page, $query);
		$this->assertCount(1, $page->filter('results'));
		$this->assertCount(1, $page->filter('persons'));
	}

	/**
	 * @group xml
	 */
	public function testTextsByTitleInXml()
	{
		$query = 'Да пробудиш драконче…';
		$page = $this->request('texts/search.xml', array('q' => $query));

		$this->assertXmlSearchPageIsFor($page, $query);
		$this->assertCount(1, $page->filter('results'));
		$this->assertCount(1, $page->filter('texts'));
	}

	/**
	 * @group xml
	 */
	public function testBooksByTitleInXml()
	{
		$query = 'Да пробудиш драконче…';
		$page = $this->request('books/search.xml', array('q' => $query));

		$this->assertXmlSearchPageIsFor($page, $query);
		$this->assertCount(1, $page->filter('results'));
		$this->assertCount(1, $page->filter('books'));
	}

	/**
	 * @group xml
	 */
	public function testSeriesByNameInXml()
	{
		$query = 'Драконче';
		$page = $this->request('series/search.xml', array('q' => $query));

		$this->assertXmlSearchPageIsFor($page, $query);
		$this->assertCount(1, $page->filter('results'));
		$this->assertCount(1, $page->filter('series'));
	}

	/**
	 * @group xml
	 */
	public function testSequencesByNameInXml()
	{
		$query = 'Хрониките на Амбър';
		$page = $this->request('sequences/search.xml', array('q' => $query));

		$this->assertXmlSearchPageIsFor($page, $query);
		$this->assertCount(1, $page->filter('results'));
		$this->assertCount(1, $page->filter('sequences'));
	}

}
