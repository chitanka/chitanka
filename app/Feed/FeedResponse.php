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
		preg_match_all('|<article.+</article>|Ums', $this->content, $matches);
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
		$newContent = $this->removeScriptContent($newContent);
		$newContent = $this->removeImageBeacons($newContent);
		$newContent = $this->removeExtraClosingTags($newContent);
		return $newContent;
	}

	/**
	 *
	 * @param string $content
	 * @return string
	 */
	protected function removeScriptContent($content) {
		$dirtyContents = $content;
		while (true) {
			$cleanedContents = preg_replace('|<\s*script[^>]*>.*<\s*/\s*script\s*>|Ums', '', $dirtyContents);
			if ($cleanedContents === $dirtyContents) {
				return $cleanedContents;
			}
			$dirtyContents = $cleanedContents;
		}
	}

	/**
	 *
	 * @param string $content
	 * @return string
	 */
	protected function removeImageBeacons($content) {
		$minWidthOrHeight = 4;
		return preg_replace_callback('|<\s*img [^>]+>|', function($match) use ($minWidthOrHeight) {
			foreach (explode(' ', $match[0]) as $attr) {
				if (strpos($attr, '=') === false) {
					continue;
				}
				list($name, $value) = explode('=', $attr);
				if ($name != 'width' && $name != 'height') {
					continue;
				}
				$intValue = trim($value, '\'"');
				if ($intValue < $minWidthOrHeight) {
					return '';
				}
			}
			return $match[0];
		}, $content);
	}

	/**
	 *
	 * @param string $content
	 * @return string
	 */
	protected function removeExtraClosingTags($content) {
		return strtr($content, array(
			'</img>' => '',
			'</br>' => '',
		));
	}
}
