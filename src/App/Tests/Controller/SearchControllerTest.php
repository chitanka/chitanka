<?php namespace App\Tests\Controller;

class SearchControllerTest extends WebTestCase {
	/**
	 * @group html
	 */
	public function testIndex() {
		$page = $this->request('search', array('q' => 'test'));

		$this->assertHtmlPageIs($page, 'search');
		$this->assertCount(1, $page->filter('h1'));
	}

	/**
	 * @group html
	 */
	public function testNonEmptySearch() {
		$page = $this->request('search', array('q' => 'фантастика'));

		$this->assertCount(0, $page->filter('.no-items'), 'There should not be “empty” message');
	}

	/**
	 * @group html
	 */
	public function testEmptySearch() {
		$page = $this->request('search', array('q' => 'testingemptysearch'));

		$this->assertCount(1, $page->filter('.no-items'), 'There should be an “empty” message');
	}

	/**
	 * @group xml
	 */
	public function testIndexInXml() {
		$query = 'Фантастика';
		$page = $this->request('search.xml', array('q' => $query));

		$this->assertXmlSearchPageIsFor($page, $query);
		$this->assertCount(1, $page->filter('results'));
	}

	/**
	 * @group xml
	 */
	public function testIndexWithEmptyQueryInXml() {
		$page = $this->request('search.xml');

		$this->assertCount(1, $page->filter('queries'));
		$this->assertCount(1, $page->filter('top'));
		$this->assertCount(1, $page->filter('latest'));
	}

	/**
	 * @group xml
	 */
	public function testPersonsByNameInXml() {
		$query = 'Николай Теллалов';
		$page = $this->request('persons/search.xml', array('q' => $query));

		$this->assertXmlSearchPageIsFor($page, $query);
		$this->assertCount(1, $page->filter('persons'));
	}

	/**
	 * @group xml
	 */
	public function testTextsByTitleInXml() {
		$query = 'Да пробудиш драконче…';
		$page = $this->request('texts/search.xml', array('q' => $query));

		$this->assertXmlSearchPageIsFor($page, $query);
		$this->assertCount(1, $page->filter('texts'));
	}

	/**
	 * @group xml
	 */
	public function testBooksByTitleInXml() {
		$query = 'Да пробудиш драконче…';
		$page = $this->request('books/search.xml', array('q' => $query));

		$this->assertXmlSearchPageIsFor($page, $query);
		$this->assertCount(1, $page->filter('books'));
	}

	/**
	 * @group xml
	 */
	public function testSeriesByNameInXml() {
		$query = 'Драконче';
		$page = $this->request('series/search.xml', array('q' => $query));

		$this->assertXmlSearchPageIsFor($page, $query);
		$this->assertCount(1, $page->filter('series'));
	}

	/**
	 * @group xml
	 */
	public function testSequencesByNameInXml() {
		$query = 'Хрониките на Амбър';
		$page = $this->request('sequences/search.xml', array('q' => $query));

		$this->assertXmlSearchPageIsFor($page, $query);
		$this->assertCount(1, $page->filter('sequences'));
	}

	/**
	 * @group osd
	 */
	public function testIndexInOsd() {
		$page = $this->request('search.osd');

		$this->assertOsdSearchPage($page);
	}

	/**
	 * @group osd
	 */
	public function testPersonsInOsd() {
		$page = $this->request('persons/search.osd');

		$this->assertOsdSearchPage($page);
	}

	/**
	 * @group osd
	 */
	public function testAuthorsInOsd() {
		$page = $this->request('authors/search.osd');

		$this->assertOsdSearchPage($page);
	}

	/**
	 * @group osd
	 */
	public function testTranslatorsInOsd() {
		$page = $this->request('translators/search.osd');

		$this->assertOsdSearchPage($page);
	}

	/**
	 * @group osd
	 */
	public function testBooksInOsd() {
		$page = $this->request('books/search.osd');

		$this->assertOsdSearchPage($page);
	}

	/**
	 * @group osd
	 */
	public function testTextsInOsd() {
		$page = $this->request('texts/search.osd');

		$this->assertOsdSearchPage($page);
	}

	/**
	 * @group osd
	 */
	public function testSeriesInOsd() {
		$page = $this->request('series/search.osd');

		$this->assertOsdSearchPage($page);
	}

	/**
	 * @group osd
	 */
	public function testSequencesInOsd() {
		$page = $this->request('sequences/search.osd');

		$this->assertOsdSearchPage($page);
	}

	/**
	 * @group suggest
	 */
	public function testPersonsSuggest() {
		$query = 'А';
		$page = $this->requestJson('persons/search.suggest', array('q' => $query));

		$this->assertSuggestSearchPageIsFor($page, $query);
	}

	/**
	 * @group suggest
	 */
	public function testAuthorsSuggest() {
		$query = 'А';
		$page = $this->requestJson('authors/search.suggest', array('q' => $query));

		$this->assertSuggestSearchPageIsFor($page, $query);
	}

	/**
	 * @group suggest
	 */
	public function testTranslatorsSuggest() {
		$query = 'А';
		$page = $this->requestJson('translators/search.suggest', array('q' => $query));

		$this->assertSuggestSearchPageIsFor($page, $query);
	}

	/**
	 * @group suggest
	 */
	public function testBooksSuggest() {
		$query = 'А';
		$page = $this->requestJson('books/search.suggest', array('q' => $query));

		$this->assertSuggestSearchPageIsFor($page, $query);
	}

	/**
	 * @group suggest
	 */
	public function testTextsSuggest() {
		$query = 'А';
		$page = $this->requestJson('texts/search.suggest', array('q' => $query));

		$this->assertSuggestSearchPageIsFor($page, $query);
	}

	/**
	 * @group suggest
	 */
	public function testSeriesSuggest() {
		$query = 'А';
		$page = $this->requestJson('series/search.suggest', array('q' => $query));

		$this->assertSuggestSearchPageIsFor($page, $query);
	}

	/**
	 * @group suggest
	 */
	public function testSequencesSuggest() {
		$query = 'А';
		$page = $this->requestJson('sequences/search.suggest', array('q' => $query));

		$this->assertSuggestSearchPageIsFor($page, $query);
	}

}
