<?php namespace App\Service;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WikiEngine {

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

	private $wikiPath;
	private $repo;

	public function __construct($wikiPath) {
		$this->wikiPath = $wikiPath;
	}

	public function getPage($filename, $withAncestors = true) {
		$filename = $this->sanitizeFileName($filename);
		try {
			list($metadata, $content) = $this->getPageSections($filename);
		} catch (NotFoundHttpException $ex) {
			$metadata = '';
			$content = null;
		}
		$ancestors = $withAncestors ? $this->getAncestors($filename) : array();
		$page = new WikiPage($filename, $content, $metadata, $ancestors);
		return $page;
	}

	public function getAncestors($filename) {
		$ancestors = array();
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
		$this->repo()->stage($fullpath)->commitWithAuthor($editSummary, $author);
	}

	public function getHistory($filename) {
		$commits = $this->repo()->getLog('master', $this->getFullPath($filename), null);
		return $commits;
	}

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

	protected function getFullPath($filename) {
		return $this->wikiPath .'/'. $this->sanitizeFileName($filename);
	}

	protected function sanitizeFileName($filename) {
		$sanitizedFilename = strtr(strtolower($filename), array(
			'_' => '-',
		));
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

	private $name;
	private $format = 'md';
	private $content;
	private $metadata;
	private $ancestors = array();

	public function __construct($name, $content, $metadata, $ancestors) {
		$this->name = $name;
		if (strpos($this->name, '.') !== false) {
			list($this->name, $this->format) = explode('.', $this->name, 2);
		}
		$this->content = $content;
		$this->metadata = $metadata;
		$this->ancestors = $ancestors;
	}

	public function exists() {
		return $this->content !== null;
	}

	public function getContent() {
		return $this->content;
	}

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

	public function hasAncestors() {
		return count($this->ancestors);
	}

	protected function getMetadata($key, $default = null) {
		if (preg_match("/$key: (.+)/", $this->metadata, $matches)) {
			return trim($matches[1]);
		}
		return $default;
	}
}
