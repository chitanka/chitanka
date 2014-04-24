<?php namespace App\Service;

class ForumService {

	const LATEST_LIMIT = 3;

	public static function fetchLatest($limit = self::LATEST_LIMIT) {
		$feedUrl = 'http://forum.chitanka.info/feed.php?c=' . $limit;
		$xsl = __DIR__.'/../Resources/transformers/forum-atom-compact.xsl';

		$feedService = new FeedService();
		$content = $feedService->fetchAndTransform($feedUrl, $xsl);
		if ($content) {
			$content = $feedService->cleanupForumFeed($content);
		}

		return $content;
	}
}
