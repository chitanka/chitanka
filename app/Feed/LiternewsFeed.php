<?php namespace App\Feed;

class LiternewsFeed {

	const LATEST_LIMIT = 3;

	public static function fetchLatest($limit = self::LATEST_LIMIT) {
		$feedUrl = 'http://planet.chitanka.info/atom.xml';
		$xsl = __DIR__.'/transformers/forum-atom-compact.xsl';

		$fetcher = new FeedFetcher();
		$response = $fetcher->fetchAndTransform($feedUrl, $xsl);
		return $response->limitArticles($limit)->cleanup();
	}
}
