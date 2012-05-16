<?php
namespace Chitanka\LibBundle\Tests\Controller;

class HistoryControllerTest extends WebTestCase
{
	/**
	 * @group html
	 */
	public function testNewTexts()
	{
		$page = $this->request("new/texts");

		$this->assertHtmlPageIs($page, 'new_texts');
	}

	/**
	 * @group html
	 */
	public function testNewBooks()
	{
		$page = $this->request("new/books");

		$this->assertHtmlPageIs($page, 'new_books');
	}

	/**
	 * @group html
	 */
	public function testNewTextsByMonth()
	{
		$page = $this->request("new/texts/2005/9");

		$this->assertHtmlPageIs($page, 'new_texts_by_month');
	}

	/**
	 * @group html
	 */
	public function testNewBooksByMonth()
	{
		$page = $this->request("new/books/2005/9");

		$this->assertHtmlPageIs($page, 'new_books_by_month');
	}

	/**
	 * @group opds
	 */
	public function testNewTextsOpds()
	{
		$route = "new/texts.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}

	/**
	 * @group opds
	 */
	public function testNewBooksOpds()
	{
		$route = "new/books.opds";
		$page = $this->request($route);

		$this->assertOpdsPageIs($page, $route);
		$this->assertCountGe(1, $page->filter('entry'));
	}
}
