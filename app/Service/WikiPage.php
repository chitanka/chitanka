<?php namespace App\Service;

class WikiPage {

	/** @var string */
	private $name;
	/** @var string */
	private $format = 'md';
	/** @var string */
	private $content;
	/** @var string */
	private $metadata;
	/** @var WikiPage[] */
	private $ancestors = [];

	/**
	 * @param string $name
	 * @param string $content
	 * @param string $metadata
	 * @param WikiPage[] $ancestors
	 */
	public function __construct($name, $content, $metadata, $ancestors) {
		$this->name = $name;
		if (strpos($this->name, '.') !== false) {
			list($this->name, $this->format) = explode('.', $this->name, 2);
		}
		$this->content = $content;
		$this->metadata = $metadata;
		$this->ancestors = $ancestors;
	}

	/**
	 * @return bool
	 */
	public function exists() {
		return $this->content !== null;
	}

	public function getContent() {
		return $this->content;
	}

	/**
	 * Get the wiki page content as HTML
	 * @return string
	 */
	public function getContentHtml() {
		if ($this->format == 'md') {
			return WikiEngine::markdownToHtml($this->content);
		}
		return $this->content;
	}

	public function getName() {
		return $this->name;
	}

	public function getFormat() {
		return $this->format;
	}

	public function getTitle() {
		return $this->getMetadata('Title', $this->name);
	}

	public function getAncestors() {
		return $this->ancestors;
	}

	/**
	 * @return bool
	 */
	public function hasAncestors() {
		return count($this->ancestors);
	}

	/**
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	protected function getMetadata($key, $default = null) {
		if (preg_match("/$key: (.+)/", $this->metadata, $matches)) {
			return trim($matches[1]);
		}
		return $default;
	}
}
