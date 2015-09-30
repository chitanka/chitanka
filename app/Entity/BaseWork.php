<?php namespace App\Entity;

use App\Service\ContentService;
use App\Util\File;
use App\Util\String;
use Sfblib\SfbConverter;
use Sfblib\SfbToHtmlConverter;

abstract class BaseWork extends Entity {

	const TITLE_NEW_LINE = "<br>\n";

	public static $ratings = [
		6 => 'Шедьовър',
		5 => 'Много добро',
		4 => 'Добро',
		3 => 'Посредствено',
		2 => 'Лошо',
		1 => 'Отвратително',
	];

	protected static $minRating = null;
	protected static $maxRating = null;
	protected static $annotationDir = 'anno';
	protected static $infoDir = 'info';

	protected $hasTitleNote = false;

	public function getDocId() {
		return 'http://chitanka.info';
	}

	public function getType() {
		return 'work';
	}

	abstract public function getId();

	/**
	 * @return string
	 */
	abstract public function getTitle();

	/**
	 * @return string
	 */
	abstract public function getSubtitle();

	/**
	 * @return array
	 */
	abstract public function getLabels();

	/**
	 * Return title and subtitle if any
	 * @param string $format   Output format: %t1 — title, %t2 — subtitle
	 * @return string
	 */
	public function getTitles($format = '%t1 — %t2') {
		if ( ($subtitle = $this->getSubtitle()) ) {
			return strtr($format, [
				'%t1' => $this->getTitle(),
				'%t2' => $subtitle,
			]);
		}

		return $this->getTitle();
	}

	/**
	 * @param int $cnt
	 * @return string
	 */
	public function getTitleAsHtml($cnt = 0) {
		$title = $this->getTitle();

		if ($this->hasTitleNote()) {
			$suffix = SfbConverter::createNoteIdSuffix($cnt, 0);
			$title .= sprintf('<sup id="ref_%s" class="ref"><a href="#note_%s">[0]</a></sup>', $suffix, $suffix);
		}

		return "<h1>$title</h1>";
	}

	/** @return array */
	abstract public function getAuthors();

	/** @return array */
	abstract public function getTranslators();

	/** @return int */
	abstract public function getYear();

	/** @return int */
	abstract public function getTransYear();

	/** @return string */
	abstract public function getPlainSeriesInfo();

	/** @return string */
	abstract public function getPlainTranslationInfo();

	/** @return string */
	abstract public function getAuthorNamesString();

	private $authorIds;
	public function getAuthorIds() {
		if ( ! isset($this->authorIds)) {
			$this->authorIds = [];
			foreach ($this->getAuthors() as $author) {
				$this->authorIds[] = $author->getId();
			}
			sort($this->authorIds);
		}

		return $this->authorIds;
	}

	public function getLang() {
		return '';
	}

	public function getOrigLang() {
		return '';
	}

	/** @return bool */
	public function isTranslation() {
		return $this->getLang() != $this->getOrigLang();
	}

	/** @return string */
	abstract public function getNameForFile();

	/** @return string */
	abstract public function getContentAsSfb();

	/** @return string */
	abstract public function getContentAsFb2();

	public function getContentAsTxt($withBom = true) {
		return ($withBom ? self::getBom() : '') . self::clearSfbMarkers($this->getContentAsSfb());
	}

	private static function clearSfbMarkers($sfbContent) {
		$sfbContent = strtr($sfbContent, [
			">\t" => "\t",
			">>\t" => "\t",
			">>>\t" => "\t",
			">>>>\t" => "\t",
			">>>>>\t" => "\t",
			"|\n" => "\n",
			"A>\n" => "\n", "A$\n" => "\n",
			"I>\n" => "\n", "I$\n" => "\n",
			"D>\n" => "\n", "D$\n" => "\n",
			"E>\n" => "\n", "E$\n" => "\n",
			"L>\n" => "\n", "L$\n" => "\n",
			"S>\n" => "\n", "S$\n" => "\n", "S\t" => "\t",
			"N>\n" => "\n", "N$\n" => "\n", "N\t" => "\t",
			"P>\n" => "\n", "P$\n" => "\n",
			"M$\n" => "\n",
			"C>\n" => "\n", "C$\n" => "\n",
			"F>\n" => "\n", "F$\n" => "\n", "F\t" => "\t",
			"T>\n" => "\n",
			"T$\n" => "\n",
			"#\t" => "\t",
			"|\t" => "\t",
			"!\t" => "\t",
			"@@\t" => "\t",
			"@\t" => "\t",
			'{s}' => '', '{/s}' => '',
			'{e}' => '', '{/e}' => '',
		]);

		$sfbContent = strtr($sfbContent, [
			"\n" => "\r\n",
		]);

		$sfbContent = preg_replace('/M>\t.+/', '', $sfbContent);

		return $sfbContent;
	}

	public function getMaxHeadersDepth() {
		$depth = 1;
		foreach ($this->getHeaders() as $header) {
			if ($depth < $header->getLevel()) {
				$depth = $header->getLevel();
			}
		}

		return $depth;
	}

	public function getHeaders() {
		return [];
	}

	public function getHeadersAsNestedXml($allowEmpty = true) {
		$xml = '';
		$prevlev = 0;
		$lastpos = -1;
		$id = -1;
		foreach ($this->getHeaders() as $i => $header) {
			if ($lastpos != $header->getFpos()) {
				$id++;
			}
			$lastpos = $header->getFpos();

			if ($prevlev < $header->getLevel()) {
				$xml .= "\n<ul>".str_repeat("<li level=$id>\n<ul>", $header->getLevel() - 1 - $prevlev);
			} else if ($prevlev > $header->getLevel()) {
				$xml .= '</li>'.str_repeat("\n</ul>\n</li>", $prevlev - $header->getLevel());
			} else {
				$xml .= '</li>';
			}
			$xml .= "\n<li level=$id>";
			$xml .= htmlspecialchars($header->getName());
			$prevlev = $header->getLevel();
		}
		if ($prevlev) {
			$xml .= '</li>'.str_repeat("\n</ul>\n</li>", $prevlev-1)."\n</ul>";
		} else if ( ! $allowEmpty ) {
			$xml = '<li level=0>Основен текст</li>';
		}

		return $xml;
	}

	private static function loadAnnotation($id) {
		$file = ContentService::getInternalContentFilePath(static::$annotationDir, $id);
		return file_exists($file) ? file_get_contents($file) : null;
	}

	private $annotation;
	public function getAnnotation() {
		return isset($this->annotation) ? $this->annotation : $this->annotation = self::loadAnnotation($this->getId());
	}

	abstract public function setHasAnno($hasAnno);

	public function setAnnotation($annotation) {
		$this->annotation = $annotation;
		$this->setHasAnno($this->annotation != '');
	}

	protected function persistAnnotation() {
		$this->saveContentInFile($this->annotation, static::$annotationDir);
	}

	public function getAnnotationAsSfb() {
		$text = $this->getAnnotation();
		if ($text) {
			$text = SfbConverter::ANNO_S . SfbConverter::EOL
				. rtrim($text) . SfbConverter::EOL
				. SfbConverter::ANNO_E . SfbConverter::EOL
				. SfbConverter::EOL;
		}

		return $text;
	}

	public function getAnnotationAsXhtml($imgDir = null) {
		$text = $this->getAnnotation();
		if ($text) {
			$converter = $this->getSfbConverter($text, $imgDir);
			$converter->convert();
			$text = $converter->getText() . $converter->getNotes(2);
		}

		return $text;
	}

	private static function loadExtraInfo($id) {
		$file = ContentService::getInternalContentFilePath(static::$infoDir, $id);
		return file_exists($file) ? file_get_contents($file) : null;
	}

	private $extraInfo;
	public function getExtraInfo() {
		return isset($this->extraInfo) ? $this->extraInfo : $this->extraInfo = self::loadExtraInfo($this->getId());
	}

	public function setExtraInfo($extraInfo) {
		$this->extraInfo = $extraInfo;
	}

	protected function persistExtraInfo() {
		$this->saveContentInFile($this->extraInfo, static::$infoDir);
	}

	/**
	 * Save a content (annotation, extra info) for the work in a file.
	 * If the content is empty, the corresponding file is deleted.
	 * @param string $content Content to save
	 * @param string $dir     Target directory
	 */
	private function saveContentInFile($content, $dir) {
		$file = ContentService::getInternalContentFilePath($dir, $this->getId());
		$fs = new \Symfony\Component\Filesystem\Filesystem();
		if ($content) {
			$fs->dumpFile($file, String::my_replace($content));
		} else if (file_exists($file) && is_file($file)) {
		        // disable until a nasty deletion bug is resolved
			//unlink($file);
		}
	}

	/**
	 * @param string $imgDir
	 */
	public function getExtraInfoAsXhtml($imgDir = null) {
		$text = $this->getExtraInfo();
		if ($text) {
			$converter = $this->getSfbConverter($text, $imgDir);
			$converter->convert();
			$text = $converter->getText() . $converter->getNotes(2);
		}

		return $text;
	}

	public function getHistoryInfo() {
		return [];
	}

	/**
	 * @param string $imgDir Image directory
	 */
	abstract public function getEpubChunks($imgDir);

	/**
	 * @param string $input  SFB content
	 * @param string $imgDir Image directory
	 */
	protected function getEpubChunksFrom($input, $imgDir) {
		$chapters = [];

		$headers = $this->getHeaders();
		if (count($headers) == 0) {
			$header = new TextHeader;
			$header->setName('Основен текст');
			$header->setFpos(0);
			$header->setLinecnt(1000000);
			$headers = [$header];
		}

		$lastpos = -1;
		foreach ($headers as $header) {
			if ($lastpos != $header->getFpos()) {
				$lastpos = $header->getFpos();
				$converter = $this->getSfbConverter($input, $imgDir);
				$converter->setStartPosition($header->getFpos());
				$converter->setMaxLineCount($header->getLinecnt());
				$converter->convert();
				$text = $converter->getText() . $converter->getNotes(2);
				$chapters[] = ['title' => $header->getName(), 'text'  => $text];
			}
		}

		return $chapters;
	}

	/** @return array */
	abstract public function getImages();

	/** @return array */
	abstract public function getThumbImages();

	/** @return bool */
	abstract public function isGamebook();

	private function getSfbConverter($file, $imgDir) {
		$conv = new SfbToHtmlConverter($file, $imgDir);
		if ($this->isGamebook()) {
			// recognize section links
			$conv->addRegExpPattern('/#(\d+)/', '<a href="#l-$1" class="ep" title="Към част $1">$1</a>');
		}

		return $conv;
	}

	public function hasTitleNote() {
		return $this->hasTitleNote;
	}

	private static function getBom() {
		return File::getBom();
	}

}
