<?php namespace App\Service;

class InternalNewsService {

	const LATEST_LIMIT = 5;

	public static function fetchLatest($limit = self::LATEST_LIMIT) {
		$feedUrl = 'http://identi.ca/api/statuses/user_timeline/127745.atom';
		$xsl = __DIR__.'/../Resources/transformers/forum-atom-compact.xsl';

		$feedService = new FeedService();
		$content = $feedService->fetchAndTransform($feedUrl, $xsl);
		if ($content) {
			$content = $feedService->limitArticles($content, $limit);
		}

		return $content;
	}
}
