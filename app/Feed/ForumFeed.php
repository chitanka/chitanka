<?php namespace App\Feed;

class ForumFeed {

	const LATEST_LIMIT = 3;

	private $feedUrl;

	public function __construct(string $feedUrl) {
		$this->feedUrl = $feedUrl;
	}

	public function fetchLatest(int $limit = self::LATEST_LIMIT) {
		$feedUrl = str_replace('LIMIT', $limit, $this->feedUrl);
		$xsl = __DIR__.'/transformers/forum-atom-compact.xsl';

		$fetcher = new FeedFetcher();
		$response = new ForumFeedResponse($fetcher->fetchAndTransform($feedUrl, $xsl));
		return $response->cleanup();
	}
}
