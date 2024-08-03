<?php namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class WebTestCase extends BaseTestCase {

	/**
	 * Make a browser request and return the crawler
	 * @param string $route
	 * @param array $parameters
	 * @return Crawler
	 */
	public function request($route, $parameters = []) {
		$client = static::createClient();

		return $client->request('GET', "/$route", $parameters);
	}

	/**
	 * @param string $route
	 * @param array $parameters
	 */
	public function requestJson($route, $parameters = []) {
		$client = static::createClient();
		$client->request('GET', "/$route", $parameters);

		return json_decode($client->getResponse()->getContent());
	}

	/**
	 * @param Crawler $page
	 * @param string $route
	 */
	public function assertHtmlPageIs(Crawler $page, $route) {
		$class = "page-$route";
		$this->assertCount(1, $page->filter("body.$class"), "HTML page body should have the class '$class'.");
	}

	/**
	 * @param Crawler $page
	 * @param string $route
	 */
	public function assertOpdsPageIs(Crawler $page, $route) {
		$this->assertContains("/$route", $page->filter("feed > id")->text(), "Opds page body should have an id containing '/$route'.");
		$this->assertCount(1, $page->filter('feed'));
	}

	/**
	 * @param Crawler $page
	 * @param string $query
	 */
	public function	assertXmlSearchPageIsFor(Crawler $page, $query) {
		$this->assertEquals($query, $page->filter('results')->attr('query'));
	}

	/**
	 * @param Crawler $page
	 */
	public function assertOsdSearchPage(Crawler $page) {
		$this->assertEquals('OpenSearchDescription', $page->getNode(0)->nodeName);
	}

	/**
	 * @param Crawler $page
	 * @param string $query
	 */
	public function assertSuggestSearchPageIsFor($page, $query) {
		$this->assertEquals(4, count($page));
		$this->assertEquals($query, $page[0]);
		$this->assertTrue(count($page[1]) == count($page[2]));
		$this->assertTrue(count($page[2]) == count($page[3]));
		$this->assertTrue(count($page[1]) > 0);
	}

	/**
	 * @param int $lowerLimit
	 * @param Crawler $elements
	 */
	public function assertCountGe($lowerLimit, Crawler $elements) {
		$this->assertTrue($elements->count() >= $lowerLimit);
	}

}
