<?php namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class WebTestCase extends BaseTestCase
{
	/**
	 * Make a browser request and return the crawler
	 * @param string $route
	 * @return Crawler
	 */
	public function request($route, $parameters = array()) {
		$client = static::createClient();

		return $client->request('GET', "/$route", $parameters);
	}

	public function requestJson($route, $parameters = array()) {
		$client = static::createClient();
		$page = $client->request('GET', "/$route", $parameters);

		return json_decode($client->getResponse()->getContent());
	}

	public function assertHtmlPageIs(Crawler $page, $route) {
		$class = "page-$route";
		$this->assertCount(1, $page->filter("body.$class"), "HTML page body should have the class '$class'.");
	}

	public function assertOpdsPageIs(Crawler $page, $route) {
		$this->assertContains("/$route", $page->filter("feed > id")->text(), "Opds page body should have an id containing '/$route'.");
		$this->assertCount(1, $page->filter('feed'));
	}

	public function	assertXmlSearchPageIsFor(Crawler $page, $query) {
		$this->assertEquals($query, $page->filter('results')->attr('query'));
	}

	public function assertOsdSearchPage(Crawler $page) {
		$this->assertEquals('OpenSearchDescription', $page->getNode(0)->nodeName);
	}

	public function assertSuggestSearchPageIsFor($page, $query) {
		$this->assertEquals(4, count($page));
		$this->assertEquals($query, $page[0]);
		$this->assertTrue(count($page[1]) == count($page[2]));
		$this->assertTrue(count($page[2]) == count($page[3]));
		$this->assertTrue(count($page[1]) > 0);
	}

	public function assertCountGe($lowerLimit, Crawler $elements) {
		$this->assertTrue($elements->count() >= $lowerLimit);
	}

}
