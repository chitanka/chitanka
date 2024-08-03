<?php namespace App\Feed;

class FeedResponse {

	const ARTICLE_ELEMENT = 'my-article';

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
		return strtr($this->content, [
			'<'.self::ARTICLE_ELEMENT.' ' => '<article ',
			'</'.self::ARTICLE_ELEMENT.'>' => '</article>',
		]);
	}

	/**
	 * Is the feed content empty?
	 * @return bool
	 */
	public function isEmpty() {
		return empty($this->content);
	}

	public function __toString() {
		return $this->getContent();
	}

	/**
	 * Limit articles in the feed but try to retain some diversity by including articles from multiple sources.
	 * This means that if the feed starts with multiple articles from a given source, only the first one is retained.
	 */
	public function limitArticles(int $limit) {
		$elem = self::ARTICLE_ELEMENT;
		if (!preg_match_all("|<$elem.+</$elem>|Ums", $this->content, $matches)) {
			return $this;
		}
		$selectArticlesAtPosition = function(array $groupedArticles, int $position) {
			return array_filter(array_map(function(array $articles) use ($position) {
				return $articles[$position] ?? null;
			}, $groupedArticles));
		};
		$articles = $matches[0];
		$groupedArticles = $this->groupArticlesByHost($articles);
		$selectedArticles = [];
		for ($i = 0, $count = count($articles); $i < $count; $i++) {
			$selectedArticles = array_merge($selectedArticles, array_values($selectArticlesAtPosition($groupedArticles, $i)));
			if (count($selectedArticles) >= $limit) {
				break;
			}
		}
		$selectedArticles = array_slice($selectedArticles, 0, $limit);
		$this->content = implode('', $selectedArticles);
		return $this;
	}

	/** @return array<string,array<string>> */
	protected function groupArticlesByHost(array $articles): array {
		$fetchArticleLinkHost = function(string $articleContentAsHtml) {
			if (preg_match('/<a href="(.+)"/U', $articleContentAsHtml, $m)) {
				return parse_url($m[1], PHP_URL_HOST);
			}
			return '';
		};
		$groupedArticles = [];
		foreach ($articles as $article) {
			$linkHost = $fetchArticleLinkHost($article);
			$groupedArticles[$linkHost][] = $article;
		}
		return $groupedArticles;
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
