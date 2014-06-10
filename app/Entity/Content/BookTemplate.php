<?php namespace App\Entity\Content;

use App\Entity\Book;
use App\Entity\Text;
use App\Util\File;
use Sfblib\SfbConverter;
use Sfblib\SfbToHtmlConverter;

class BookTemplate {

	private $book;

	public function __construct(Book $book) {
		$this->book = $book;
	}

	private $sfb;
	public function generateSfb() {
		if (isset($this->sfb)) {
			return $this->sfb;
		}

		$this->sfb = '';
		foreach ($this->getContentAsArray() as $line) {
			$this->sfb .= $this->generateSfbForLine($line);
		}
		return $this->sfb;
	}

	private function generateSfbForLine($line) {
		$line = rtrim($line, "\t");
		if (empty($line)) {
			return SfbConverter::EOL;
		}
		$lineParts = explode("\t", $line);
		if (count($lineParts) == 1) {
			return $line . SfbConverter::EOL;
		}
		list($command, $content) = $lineParts;
		if ( ! preg_match('/\{(title|text|file):(\d+)(-[^|]+)?(\|(.+))?\}/', $content, $matches)) {
			return $line . SfbConverter::EOL;
		}
		$text = $this->book->getTextById($matches[2]);
		switch ($matches[1]) {
			case 'title':
				return $this->generateSfbForTitleLine($text, $command) . SfbConverter::EOL;
			case 'text':
				return $this->generateSfbForTextLine($text, $command, $matches) . SfbConverter::EOL;
			case 'file':
				return $this->generateSfbForFileLine($text, $command, $matches);
		}
	}

	private function generateSfbForTitleLine(Text $text, $command) {
		$authors = implode(', ', $this->book->getBookAuthorIfNotInTitle($text));
		if ( ! empty($authors) ) {
			$authors = $command . SfbConverter::CMD_DELIM . $authors . SfbConverter::EOL;
		}
		return $authors . strtr($text->getTitleAsSfb(), array(SfbConverter::HEADER => $command));
	}

	/**
	 * @param string[] $matches
	 */
	private function generateSfbForTextLine(Text $text, $command, $matches) {
		if (isset($matches[5])) {
			$title = $command . SfbConverter::CMD_DELIM . $matches[5];
		} else {
			$title = $this->generateSfbForTitleLine($text, $command);
		}
		$textContent = $text->getRawContent();
		if (strpos($textContent, SfbConverter::EOL.">") !== false && $textContent[0] !== '>' && strpos($textContent, "\t{img:") !== 0) {
			$textContent = $command . SfbConverter::CMD_DELIM . SfbConverter::EOL . $textContent;
		}
		return $title . str_repeat(SfbConverter::EOL, 2) . $this->replaceSfbHeadings($textContent, $command);
	}

	/**
	 * @param string[] $matches
	 */
	private function generateSfbForFileLine(Text $text, $command, $matches) {
		if (empty($matches[3])) {
			$textContent = $text->getRawContent();
		} else {
			$textContent = File::getContentFile('text', $matches[2].$matches[3]);
		}
		if (empty($command)) {
			return $textContent;
		}
		return $this->replaceSfbHeadings($textContent, $command);
	}

	private $headingRepl = array(
		'>' => array(
			"\n>" => "\n>>",
			"\n>>" => "\n>>>",
			"\n>>>" => "\n>>>>",
			"\n>>>>" => "\n>>>>>",
			"\n>>>>>" => "\n#",
		),
		'>>' => array(
			"\n>" => "\n>>>",
			"\n>>" => "\n>>>>",
			"\n>>>" => "\n>>>>>",
			"\n>>>>" => "\n#",
			"\n>>>>>" => "\n#",
		),
		'>>>' => array(
			"\n>" => "\n>>>>",
			"\n>>" => "\n>>>>>",
			"\n>>>" => "\n#",
			"\n>>>>" => "\n#",
			"\n>>>>>" => "\n#",
		),
		'>>>>' => array(
			"\n>" => "\n>>>>>",
			"\n>>" => "\n#",
			"\n>>>" => "\n#",
			"\n>>>>" => "\n#",
			"\n>>>>>" => "\n#",
		),
		'>>>>>' => array(
			"\n>" => "\n#",
			"\n>>" => "\n#",
			"\n>>>" => "\n#",
			"\n>>>>" => "\n#",
			"\n>>>>>" => "\n#",
		),
	);

	/**
	 * @param string|null $content
	 */
	private function replaceSfbHeadings($content, $startHeading) {
		return ltrim(strtr("\n".$content, $this->headingRepl[$startHeading]), "\n");
	}

	public function getAsXhtml() {
		$template = $this->getContent();
		if (empty($template)) {
			return '';
		}
		$template = preg_replace('/\t\{img:[^}]+\}/', '', $template);
		$imgDir = 'IMG_DIR_PREFIX' . File::getContentFilePath('book-img', $this->book->getId()).'/';
		$converter = new SfbToHtmlConverter($template, $imgDir);
		$content = $converter->convert()->getContent();
		//$content = preg_replace('|<p>\n\{(\d+)\}\n</p>|', '{$1}', $content);
		$content = preg_replace('#<h(\d)>\{(title|text):(\d+)\}</h\d>#', '<h$1 class="inner-text">{text:$3}</h$1>', $content);
		$content = preg_replace('#<h(\d)>([^{].+)</h\d>#', '<h$1 class="inline-text">$2</h$1>', $content);
		// remove comments
		$content = preg_replace('/&lt;!--.+--&gt;/U', '', $content);
		$content = strtr($content, array("<p>\n----\n</p>" => '<hr/>'));
		$content = preg_replace_callback('#<h(\d)>\{file:(.+)\}</h\d>#', function($matches) {
			return ''; // disable

			$headingLevel = $matches[1];
			$file = $matches[2];

			$imgDir = File::getContentFilePath('book-img', (int) $file) . '/';
			$converter = new SfbToHtmlConverter(File::getContentFile('text', $file), $imgDir);

			return $converter->convert()->getContent();
		}, $content);

		return $content;
	}

	public function hasAutohide() {
		return strpos($this->getContent(), '<!--AUTOHIDE-->') !== false;
	}

	private function getContentAsArray() {
		return explode("\n", $this->clearSpecialBookSyntax($this->getContent()));
	}

	private $content;
	public function getContent() {
		return $this->content ?: $this->content = File::getContentFile('book', $this->book->getId());
	}
	public function setContent($content) {
		file_put_contents(File::getContentFilePath('book', $this->book->getId()), $content);
		$this->content = $content;
		$this->textIds = null;
	}

	private $textIds;
	public function getTextIds() {
		return isset($this->textIds) ? $this->textIds : $this->textIds = $this->extractTextIds($this->getContent()) ;
	}

	static public function extractTextIds($template) {
		if (preg_match_all('/\{(file|text):(\d+)/', $template, $matches)) {
			return $matches[2];
		}
		return array();
	}

	private function clearSpecialBookSyntax($template) {
		return strtr($template, array(
			"\t<!--AUTOHIDE-->\n" => '',
		));
	}
}
