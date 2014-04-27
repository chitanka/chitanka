<?php namespace App\Feed;

class ForumFeed {

	const LATEST_LIMIT = 3;

	public static function fetchLatest($limit = self::LATEST_LIMIT) {
		$feedUrl = 'http://forum.chitanka.info/feed.php?c=' . $limit;
		$xsl = __DIR__.'/transformers/forum-atom-compact.xsl';

		$fetcher = new FeedFetcher();
		$response = new ForumFeedResponse($fetcher->fetchAndTransform($feedUrl, $xsl));
		return $response->cleanup();
	}
}
