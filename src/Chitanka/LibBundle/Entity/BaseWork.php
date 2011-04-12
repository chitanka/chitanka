<?php
namespace Chitanka\LibBundle\Entity;

use Chitanka\LibBundle\Util\Ary;
use Chitanka\LibBundle\Util\File;
use Chitanka\LibBundle\Legacy\Legacy;

class BaseWork
{

	const TITLE_NEW_LINE = "<br>\n";

	public static
		$ratings = array(
			6 => 'Шедьовър',
			5 => 'Много добро',
			4 => 'Добро',
			3 => 'Посредствено',
			2 => 'Лошо',
			1 => 'Отвратително',
		);

	protected static
		$exts = array('.jpg', '.png');

	protected static
		$_minRating = null, $_maxRating = null;


	protected
		$annotationDir = 'anno',
		$infoDir = 'info',
		$_hasTitleNote = null;


	public function getDocId()
	{
		return 'http://chitanka.info';
	}

	public function getType()
	{
		return 'work';
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function getSubtitle()
	{
		return $this->subtitle;
	}

	/**
	* Return title and subtitle if any
	* @param string $format   Output format: %t1 — title, %t2 — subtitle
	*/
	public function getTitles($format = '%t1 — %t2')
	{
		if ( ($subtitle = $this->getSubtitle()) ) {
			return strtr($format, array(
				'%t1' => $this->getTitle(),
				'%t2' => $subtitle,
			));
		}

		return $this->getTitle();
	}

	private $authorIds;

	public function getAuthorIds()
	{
		if ( ! isset($this->authorIds)) {
			$this->authorIds = array();
			foreach ($this->getAuthors() as $author) {
				$this->authorIds[] = $author->getId();
			}
			sort($this->authorIds);
		}

		return $this->authorIds;
	}

	public function getLang()
	{
		return '';
	}

	public function getOrigLang()
	{
		return '';
	}

	public function getCover($width = null)
	{
		return null;
	}

	public function getBackCover($width = null)
	{
		return null;
	}

	public function isTranslation()
	{
		return $this->getLang() != $this->getOrigLang();
	}


	public function normalizeFileName($filename)
	{
		$filename = substr($filename, 0, 200);
		$filename = File::cleanFileName($filename);

		return $filename;
	}


	public function getContentAsTxt($withBom = true)
	{
		return ($withBom ? self::getBom() : '')
			. self::clearSfbMarkers( $this->getContentAsSfb() );
	}


	/**
	* @param $id Text or book ID
	* @param $defCover Default covers if there aren’t any for $id
	* @param $key 'cover' or 'book-cover'
	*/
	public static function getCovers($id, $defCover = null, $key = 'cover')
	{
		$bases = array( Legacy::getContentFilePath($key, $id) );
		if ( !empty($defCover) ) {
			$bases[] = Legacy::getContentFilePath($key, $defCover);
		}
		$coverFiles = Ary::cartesianProduct($bases, self::$exts);
		$covers = array();
		foreach ($coverFiles as $file) {
			if ( file_exists( $file ) ) {
				$covers[] = $file;
				// search for more images of the form “ID-DIGIT.EXT”
				for ($i = 2; /* infinity */; $i++) {
					$efile = strtr($file, array('.' => "-$i."));
					if ( file_exists( $efile ) ) {
						$covers[] = $efile;
					} else {
						break;
					}
				}
				break; // don’t check other extensions
			}
		}
		return $covers;
	}


	public static function clearSfbMarkers($sfbContent)
	{
		$sfbContent = strtr($sfbContent, array(
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
			"M>\n" => "\n", "M$\n" => "\n",
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
		));

		$sfbContent = strtr($sfbContent, array(
			"\n" => "\r\n",
		));

		return $sfbContent;
	}


	public static function renameCover($cover, $newname) {
		$rexts = strtr(implode('|', self::$exts), array('.'=>'\.'));
		return preg_replace("/\d+(-\d+)?($rexts)/", "$newname$1$2", $cover);
	}


	public function getMaxHeadersDepth()
	{
		$depth = 1;
		foreach ($this->getHeaders() as $header) {
			if ($depth < $header['level']) {
				$depth = $header['level'];
			}
		}

		return $depth;
	}

	public function getHeaders()
	{
		return array();
	}


	public function getHeadersAsNestedXml($allowEmpty = true)
	{
		$xml = '';
		$prevlev = 0;
		$lastpos = -1;
		$id = -1;
		foreach ($this->getHeaders() as $i => $header) {
			if ($lastpos != $header['fpos']) {
				$id++;
			}
			$lastpos = $header['fpos'];

			if ($prevlev < $header['level']) {
				$xml .= "\n<ul>".str_repeat("<li level=$id>\n<ul>", $header['level'] - 1 - $prevlev);
			} else if ($prevlev > $header['level']) {
				$xml .= '</li>'.str_repeat("\n</ul>\n</li>", $prevlev - $header['level']);
			} else {
				$xml .= '</li>';
			}
			$xml .= "\n<li level=$id>";
			$xml .= htmlspecialchars($header['name']);
			$prevlev = $header['level'];
		}
		if ($prevlev) {
			$xml .= '</li>'.str_repeat("\n</ul>\n</li>", $prevlev-1)."\n</ul>";
		} else if ( ! $allowEmpty ) {
			$xml = '<li level=0>Основен текст</li>';
		}

		return $xml;
	}


	public function getAnnotation()
	{
		$file = Legacy::getContentFilePath($this->annotationDir, $this->id);
		$text = '';
		if ( file_exists($file) ) {
			$text = file_get_contents($file);
		}

		return $text;
	}



	public function getAnnotationAsSfb()
	{
		$text = $this->getAnnotation();
		if ($text) {
			$text = \Sfblib_SfbConverter::ANNO_S . \Sfblib_SfbConverter::EOL
				. rtrim($text) . \Sfblib_SfbConverter::EOL
				. \Sfblib_SfbConverter::ANNO_E . \Sfblib_SfbConverter::EOL . \Sfblib_SfbConverter::EOL;
		}

		return $text;
	}


	public function getAnnotationAsXhtml($imgDir = null)
	{
		$text = $this->getAnnotation();
		if ($text) {
			$converter = $this->_getSfbConverter($text, $imgDir);
			$converter->convert();
			$text = $converter->getText() . $converter->getNotes(2);
		}

		return $text;
	}


	public function getExtraInfo() {
		$file = Legacy::getContentFilePath($this->infoDir, $this->id);
		$info = '';
		if ( file_exists($file) ) {
			$info = file_get_contents($file);
		}

		return $info;
	}


	public function getExtraInfoAsXhtml($imgDir = null)
	{
		$text = $this->getExtraInfo();
		if ($text) {
			$converter = $this->_getSfbConverter($text, $imgDir);
			$converter->convert();
			$text = $converter->getText() . $converter->getNotes(2);
		}

		return $text;
	}


	public function getHistoryInfo()
	{
		return array();
	}


	protected function getEpubChunksFrom($input, $imgDir)
	{
		$chapters = array();

		$headers = $this->getHeaders();
		if ( empty($headers) ) {
			$headers = array(array('name' => 'Основен текст', 'fpos' => 0, 'linecnt' => 1000000));
		}

		$lastpos = -1;
		foreach ($headers as $header) {
			if ($lastpos != $header['fpos']) {
				$lastpos = $header['fpos'];
				$converter = $this->_getSfbConverter($input, $imgDir);
				$converter->startpos = $header['fpos'];
				$converter->maxlinecnt = $header['linecnt'];
				$converter->convert();
				$text = $converter->getText() . $converter->getNotes(2);
				$chapters[] = array('title' => $header['name'], 'text'  => $text);
			}
		}

		return $chapters;
	}


	protected function _getSfbConverter($file, $imgDir)
	{
		$conv = new \Sfblib_SfbToHtmlConverter($file, $imgDir);
		if ($this->isGamebook()) {
			// recognize section links
			$conv->patterns['/#(\d+)/'] = '<a href="#t-_$1" class="ep" title="Към част $1">$1</a>';
		}

		return $conv;
	}


	public function hasTitleNote()
	{
		return false;
	}


	public static function getBom($withEncoding = true)
	{
		$bom = "\xEF\xBB\xBF"; // Byte order mark for some windows software

		if ($withEncoding) {
			$bom .= "\t[Kodirane UTF-8]\n\n";
		}

		return $bom;
	}

}
