<?php namespace App\Feed;

class LiternewsFeed {

	const LATEST_LIMIT = 3;

	private $feedUrl;

	public function __construct(string $feedUrl) {
		$this->feedUrl = $feedUrl;
	}

	public function fetchLatest(int $limit = self::LATEST_LIMIT) {
		$xsl = __DIR__.'/transformers/forum-atom-compact.xsl';

		$fetcher = new FeedFetcher();
		$response = $fetcher->fetchAndTransform($this->feedUrl, $xsl);
		return $response->limitArticles($limit)->cleanup();
	}
}
