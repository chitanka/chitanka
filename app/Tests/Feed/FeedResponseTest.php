<?php namespace App\Tests\Feed;

use App\Feed\FeedResponse;
use App\Tests\TestCase;

class FeedResponseTest extends TestCase {

	/** @dataProvider data_limitArticles */
	public function test_limitArticles(string $articles, int $limit, string $expectedContent) {
		$response = new FeedResponse($articles);
		$response->limitArticles($limit);
		$this->assertSame($expectedContent, $response->getContent());
	}
	public function data_limitArticles() {
		$article = function($url, $idx) {
			return "<my-article class=\"post collapsible\"><h1><a href=\"https://$url\">heading $idx</a></h1><div>content $idx from $url</div></my-article>";
		};
		$articleHostA_1 = $article('hostA/a1', 1);
		$articleHostA_2 = $article('hostA/a2', 2);
		$articleHostA_3 = $article('hostA/a3', 3);
		$articleHostB_1 = $article('hostB/a1', 1);
		$articleHostB_2 = $article('hostB/a2', 2);
		$articleHostC_1 = $article('hostC/a1', 1);
		$allArticles = implode('', [
			$articleHostA_1, $articleHostA_2, $articleHostA_3,
			$articleHostB_1, $articleHostB_2,
			$articleHostC_1,
		]);
		$expected = function(...$articles) {
			return str_replace('my-article', 'article', implode('', $articles));
		};
		return [
			['', 2, ''],
			[$allArticles, 3, $expected($articleHostA_1, $articleHostB_1, $articleHostC_1)],
			[$allArticles, 5, $expected($articleHostA_1, $articleHostB_1, $articleHostC_1, $articleHostA_2, $articleHostB_2)],
			[$allArticles, 6, $expected($articleHostA_1, $articleHostB_1, $articleHostC_1, $articleHostA_2, $articleHostB_2, $articleHostA_3)],
			[$allArticles, 100, $expected($articleHostA_1, $articleHostB_1, $articleHostC_1, $articleHostA_2, $articleHostB_2, $articleHostA_3)],
		];
	}
}
