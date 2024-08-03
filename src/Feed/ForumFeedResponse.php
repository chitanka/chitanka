<?php namespace App\Feed;

class ForumFeedResponse extends FeedResponse {

	/**
	 * @param string $content
	 * @return string
	 */
	protected function cleanupContent($content) {
		$cleanContent = parent::cleanupContent($content);
		$cleanContent = FeedCleaner::cleanupPhpbbContent($cleanContent);
		return $cleanContent;
	}

}
