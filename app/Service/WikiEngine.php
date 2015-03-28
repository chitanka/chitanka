<?php namespace App\Service;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WikiEngine {

	/**
	 * Convert a text from Markdown into HTML
	 * @param string $markdownContent
	 * @return string
	 */
	public static function markdownToHtml($markdownContent) {
		$html = Markdown::defaultTransform($markdownContent);
		$html = preg_replace_callback('#<p>(<img [^>]+>)</p>#', function($match) {
			if (preg_match('#title="([^"]+)"#', $match[1], $submatch)) {
				return '<p class="image">'
					. $match[1] . '<span class="image-title">'.$submatch[1].'</span>'
					. '</p>';
			}
			return '<p class="image">'.$match[1].'</p>';
		}, $html);
		return $html;
	}

	/** @var string */
	private $wikiPath;
	/** @var GitRepository */
	private $repo;

	/**
	 * @param string $wikiPath
	 */
	public function __construct($wikiPath) {
		$this->wikiPath = $wikiPath;
	}

	/**
	 * @param string $filename
	 * @param bool $withAncestors
	 * @return WikiPage
	 */
	public function getPage($filename, $withAncestors = true) {
		$filename = $this->sanitizeFileName($filename);
		try {
			list($metadata, $content) = $this->getPageSections($filename);
		} catch (NotFoundHttpException $ex) {
			$metadata = '';
			$content = null;
		}
		$ancestors = $withAncestors ? $this->getAncestors($filename) : [];
		$page = new WikiPage($filename, $content, $metadata, $ancestors);
		return $page;
	}

	/**
	 * @param string $filename
	 * @return WikiPage[]
	 */
	public function getAncestors($filename) {
		$ancestors = [];
		if (strpos($filename, '/') !== false) {
			$ancestorNames = explode('/', $filename);
			array_pop($ancestorNames);
			$currentAncestorName = '';
			foreach ($ancestorNames as $ancestorName) {
				$currentAncestorName .= '/'.$ancestorName;
				$ancestors[] = $this->getPage($currentAncestorName, false);
			}
		}
		return $ancestors;
	}

	/**
	 *
	 * @param string $editSummary
	 * @param string $filename
	 * @param string $content
	 * @param string $title
	 * @param string $author
	 */
	public function savePage($editSummary, $filename, $content, $title = null, $author = null) {
		$fullpath = $this->getFullPath($filename);
		$title = $title ? trim($title) : $filename;
		$content = trim($content) . "\n";
		$fullContent = "Title: $title\n\n$content";
		if (!file_exists($dir = dirname($fullpath))) {
			mkdir($dir, 0755, true);
		}
		file_put_contents($fullpath, $fullContent);
		$editSummary = '['.$this->sanitizeFileName($filename).'] '.$editSummary;
		$this->repo()->stageAndCommitWithAuthor($fullpath, $editSummary, $author);
	}

	/**
	 * @param string $filename
     * @return \GitElephant\Objects\Log
	 */
	public function getHistory($filename) {
		$commits = $this->repo()->getLog('master', $this->getFullPath($filename), null);
		return $commits;
	}

	/**
	 * @param string $filename
	 * @return array
	 */
	protected function getPageSections($filename) {
		$fullpath = $this->getFullPath($filename);
		if (!file_exists($fullpath)) {
			throw new NotFoundHttpException("Page '$filename' does not exist.");
		}
		$sections = explode("\n\n", file_get_contents($fullpath), 2);
		if (count($sections) < 2) {
			array_unshift($sections, '');
		}
		return $sections;
	}

	/**
	 * @param string $filename
	 * @return string
	 */
	protected function getFullPath($filename) {
		return $this->wikiPath .'/'. $this->sanitizeFileName($filename);
	}

	/**
	 * @param string $filename
	 * @return string
	 */
	protected function sanitizeFileName($filename) {
		$sanitizedFilename = strtr(strtolower($filename), [
			'_' => '-',
		]);
		$sanitizedFilename = preg_replace('#[^a-z\d/.-]#', '', $sanitizedFilename);
		$sanitizedFilename = ltrim($sanitizedFilename, '/.');
		if (strpos($sanitizedFilename, '.') === false) {
			$sanitizedFilename .= '.md';
		}
		return $sanitizedFilename;
	}

	/** @return GitRepository */
	protected function repo() {
		return $this->repo ?: $this->repo = new GitRepository($this->wikiPath);
	}
}

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
