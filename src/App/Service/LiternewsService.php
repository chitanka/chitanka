<?php namespace App\Service;

class LiternewsService {

	const LATEST_LIMIT = 3;

	public static function fetchLatest($limit = self::LATEST_LIMIT) {
		$feedUrl = 'http://planet.chitanka.info/atom.xml';
		$xsl = __DIR__.'/../Resources/transformers/forum-atom-compact.xsl';

		$feedService = new FeedService();
		$content = $feedService->fetchAndTransform($feedUrl, $xsl);
		if ($content) {
			$content = $feedService->limitArticles($content, $limit);
			$content = $feedService->cleanup($content);
		}

		return $content;
	}
}
