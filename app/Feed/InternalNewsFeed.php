<?php namespace App\Feed;

class InternalNewsFeed {

	const LATEST_LIMIT = 5;

	public static function fetchLatest($limit = self::LATEST_LIMIT) {
		$feedUrl = 'http://identi.ca/api/statuses/user_timeline/127745.atom';
		$xsl = __DIR__.'/transformers/forum-atom-compact.xsl';

		$fetcher = new FeedFetcher();
		$response = $fetcher->fetchAndTransform($feedUrl, $xsl);
		return $response->limitArticles($limit)->cleanup();
	}
}
