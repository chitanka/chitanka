<?php namespace App\Feed;

class FeedResponse {

	protected $content;

	public function __construct($content = '') {
		$this->setContent($content);
	}

	/**
	 *
	 * @param string|FeedResponse $content
	 */
	public function setContent($content) {
		if ($content instanceof self) {
			$content = $content->content;
		}
		$this->content = $content;
		return $this;
	}

	public function getContent() {
		return $this->content;
	}

	/**
	 * Is the feed content empty?
	 * @return bool
	 */
	public function isEmpty() {
		return empty($this->content);
	}

	public function __toString() {
		return $this->content;
	}

	/**
	 * Limit articles in the feed
	 * @param int $limit
	 * @return FeedResponse
	 */
	public function limitArticles($limit) {
		if (!preg_match_all('|<article.+</article>|Ums', $this->content, $matches)) {
			return $this;
		}
		$newContent = '';
		for ($i = 0; $i < $limit; $i++) {
			$newContent .= $matches[0][$i];
		}
		$this->content = $newContent;
		return $this;
	}

	/**
	 * Clean up the feed contents. Remove possible malicious input.
	 * @return FeedResponse
	 */
	public function cleanup() {
		$this->content = $this->cleanupContent($this->content);
		return $this;
	}

	/**
	 * Clean up given content
	 * @param string $content
	 * @return string
	 */
	protected function cleanupContent($content) {
		$newContent = $content;
		$newContent = FeedCleaner::removeScriptContent($newContent);
		$newContent = FeedCleaner::removeImageBeacons($newContent);
		$newContent = FeedCleaner::removeSocialFooterLinks($newContent);
		$newContent = FeedCleaner::removeExtraClosingTags($newContent);
		$newContent = FeedCleaner::relativizeUrlProtocol($newContent);
		return $newContent;
	}

}
