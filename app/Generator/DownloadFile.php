<?php namespace App\Generator;

use App\Entity\BaseWork;
use App\Entity\Book;
use App\Entity\Text;
use App\Legacy\CacheManager;
use App\Legacy\Setup;
use App\Util\Char;
use App\Util\File;
use Sfblib\SfbConverter;

class DownloadFile {

	private static $dlDir = 'cache/dl';
	private $zipFile;

	public function __construct() {
		$this->zipFile = new ZipFile;
	}

	/**
	 * @param Book $book
	 */
	public function getSfbForBook(Book $book) {
		return $this->getDlFileForBook($book, 'sfb', 'addBinariesForSfb');
	}

	private function addBinariesForSfb(Book $book, $filename) {
		if ( ($cover = $book->getCover()) ) {
			$this->addFileEntry($cover, $filename);
		}
		foreach ($book->getImages() as $image) {
			$this->addFileEntry($image);
		}
	}

	public function getTxtForBook(Book $book) {
		return $this->getDlFileForBook($book, 'txt');
	}

	public function getFb2ForBook(Book $book) {
		return $this->getDlFileForBook($book, 'fb2');
	}

	public function getEpubForBook(Book $book) {
		return $this->getDlFileForBook($book, 'epub', 'addEpubEntries');
	}

	public function getStaticFileForBook(Book $book, $format) {
		$dlFileName = $this->getFullDlFileName($this->createWorkFileName($book, $format));
		if (file_exists($dlFileName)) {
			return $dlFileName;
		}
		$sourceFile = $book->getStaticFile($format);
		if ( !file_exists($sourceFile)) {
			throw new \Exception("$format file for book #{$book->getId()} does not exist: '$sourceFile'");
		}
		copy($sourceFile, $dlFileName);

		return $dlFileName;
	}

	public function getEpubForText(Text $text) {
		return $this->getDlFileForText($text, 'epub', 'addEpubEntries');
	}

	public function getEpubForTexts($texts) {
		return $this->getDlFileForTexts($texts, 'epub', 'addEpubEntries');
	}

	/**
	 * @param Book $book
	 * @param string $format
	 * @param callback $binaryCallback
	 */
	public function getDlFileForBook(Book $book, $format, $binaryCallback = null) {
		$textIds = $book->getTextIds();
		// a book with one text is different from the very same text
		$textIds[] = "book".$book->getId();

		if ( ($dlCache = self::getDlCache($textIds, $format)) ) {
			if ( ($dlFile = self::getDlFile($dlCache)) ) {
				return $dlFile;
			}
		}

		$filename = $this->createWorkFileName($book);

		$getMethod = sprintf('getContentAs%s', ucfirst($format));
		if ( method_exists($book, $getMethod) ) {
			$cacheKey = "book-".$book->getId().".$format";
			$this->addContentEntry($book->$getMethod(), "$filename.$format", $cacheKey);
		}

		if ($binaryCallback && method_exists($this, $binaryCallback)) {
			$this->$binaryCallback($book, $filename);
		}

		$zipFilename = "$filename.$format";
		if ($format != 'epub') {
			$zipFilename .= '.zip';
		}

		// TODO
		self::setDlCache($textIds, $zipFilename, $format);
		self::setDlFile($zipFilename, $this->zipFile->file());

		return self::getDlFile($zipFilename);
	}

	/**
	 * @param Text[] $texts
	 * @param string $format
	 * @param callback $binaryCallback
	 */
	public function getDlFileForTexts($texts, $format, $binaryCallback = null) {
		$textIds = array_map(function(Text $text) {
			return $text->getId();
		}, $texts);
		if ( ($dlCache = self::getDlCache($textIds, $format)) ) {
			if ( ($dlFile = self::getDlFile($dlCache)) ) {
				return $dlFile;
			}
		}

		foreach ($texts as $text) {
			$dlFile = new DownloadFile;
			$filename = $dlFile->getDlFileForText($text, $format, $binaryCallback);
			$this->zipFile->addNewFileEntry(file_get_contents($filename), basename($filename), 0, false);
		}

		$zipFilename = sprintf('chitanka-info-%d-files-%s-%s.zip', count($texts), uniqid(), $format);
		// TODO
		self::setDlCache($textIds, $zipFilename, $format);
		self::setDlFile($zipFilename, $this->zipFile->file());

		return self::getDlFile($zipFilename);
	}

	/**
	 * @param Text $text
	 * @param string $format
	 * @param callback $binaryCallback
	 * @return string File name
	 */
	public function getDlFileForText(Text $text, $format, $binaryCallback = null) {
		$textIds = [$text->getId()];

		if ( ($dlCache = self::getDlCache($textIds, $format)) ) {
			if ( ($dlFile = self::getDlFile($dlCache)) ) {
				return $dlFile;
			}
		}

		$filename = $this->createWorkFileName($text);

		$getMethod = sprintf('getContentAs%s', ucfirst($format));
		if ( method_exists($text, $getMethod) ) {
			$cacheKey = "text-".$text->getId().".$format";
			$this->addContentEntry($text->$getMethod(), "$filename.$format", $cacheKey);
		}

		if ($binaryCallback && method_exists($this, $binaryCallback)) {
			$this->$binaryCallback($text, $filename);
		}

		$zipFilename = "$filename.$format";
		if ($format != 'epub') {
			$zipFilename .= '.zip';
		}

		// TODO
		self::setDlCache($textIds, $zipFilename, $format);
		self::setDlFile($zipFilename, $this->zipFile->file());

		return self::getDlFile($zipFilename);
	}

	private function addEpubEntries($work) {
		$epubFile = new EpubFile($work);

		$file = $epubFile->getMimetypeFile();
		$this->addContentEntry($file['content'], $file['name'], null, false);

		$file = $epubFile->getContainerFile();
		$this->addContentEntry($file['content'], $file['name']);

		$file = $epubFile->getCssFile();
		$this->addContentEntry($file['content'], $file['name']);

		if ($work instanceof Book) {
			$this->addCoverForEpub($work, $epubFile);
			$this->addBackCoverForEpub($work, $epubFile);
		}

		$file = $epubFile->getTitlePageFile();
		$this->addContentEntry($file['content'], $file['name']);
		$epubFile->addItem($file['name'], $file['title'], 'pre');

		$this->addAnnotationForEpub($epubFile);
		$this->addExtraInfoForEpub($epubFile);
		$this->addChaptersForEpub($work, $epubFile);
		$this->addImagesForEpub($work, $epubFile);

		$file = $epubFile->getCreditsFile();
		$this->addContentEntry($file['content'], $file['name']);
		$epubFile->addItem($file['name'], $file['title'], 'post');

		$file = $epubFile->getTocFile();
		$this->addContentEntry($file['content'], $file['name']);

		$file = $epubFile->getContentFile();
		$this->addContentEntry($file['content'], $file['name']);
	}

	/**
	 * @param EpubFile $epubFile
	 */
	private function addAnnotationForEpub($epubFile) {
		if ( ($file = $epubFile->getAnnotation()) ) {
			$this->addContentEntry($file['content'], $file['name']);
			$epubFile->addItem($file['name'], $file['title'], 'pre');
		}
	}

	/**
	 * @param EpubFile $epubFile
	 */
	private function addExtraInfoForEpub($epubFile) {
		if ( ($file = $epubFile->getExtraInfo()) ) {
			$this->addContentEntry($file['content'], $file['name']);
			$epubFile->addItem($file['name'], $file['title'], 'post');
		}
	}

	/**
	 * @param BaseWork $work
	 * @param EpubFile $epubFile
	 */
	private function addChaptersForEpub(BaseWork $work, $epubFile) {
		$curObjCount = SfbConverter::getObjectCount();
		$chapters = $work->getEpubChunks($epubFile->getImagesDir(false));
		foreach ($chapters as $i => $chapter) {
			$file = $epubFile->getItemFileName($i);
			$text = $chapter['text'];
			if ( $i == 0 && $work->hasTitleNote() ) {
				$text = $work->getTitleAsHtml($curObjCount+1) . $text;
			}
			$text = $epubFile->getXhtmlContent($text, $chapter['title']);
			$this->addContentEntry($text, $file);
			$epubFile->addItem($file);
		}
	}

	/**
	 * @param Book $book
	 * @param EpubFile $epubFile
	 */
	private function addCoverForEpub(Book $book, EpubFile $epubFile) {
		if ( ($cover = $book->getCover(400)) ) {
			$file = $this->addFileEntry($cover, $epubFile->getCoverFileName());
			$epubFile->addCover($file);
		}
		if ( ($file = $epubFile->getCoverPageFile()) ) {
			$this->addContentEntry($file['content'], $file['name']);
			$epubFile->addItem($file['name'], $file['title'], 'pre');
		}
	}

	/**
	 * @param Book $book
	 * @param EpubFile $epubFile
	 */
	private function addBackCoverForEpub(Book $book, EpubFile $epubFile) {
		if ( ($cover = $book->getBackCover(400)) ) {
			$file = $this->addFileEntry($cover, $epubFile->getBackCoverFileName());
			$epubFile->addBackCover($file);
		}
		if ( ($file = $epubFile->getBackCoverPageFile()) ) {
			$this->addContentEntry($file['content'], $file['name']);
			$epubFile->addItem($file['name'], $file['title'], 'post');
		}
	}

	/**
	 * @param BaseWork $work
	 * @param EpubFile $epubFile
	 */
	private function addImagesForEpub(BaseWork $work, EpubFile $epubFile) {
		$imagesDir = $epubFile->getImagesDir();

		$thumbs = [];
		foreach ($work->getThumbImages() as $i => $image) {
			$file = $this->addFileEntry($image, "$imagesDir/thumb/");
			$epubFile->addFile("image-thumb-$i", $file);
			$thumbs[] = basename($image);
		}

		foreach ($work->getImages() as $i => $image) {
			// for now skip thumbnailed images; may change in the future
			if ( ! in_array(basename($image), $thumbs) ) {
				$file = $this->addFileEntry($image, "$imagesDir/");
				$epubFile->addFile("image-$i", $file);
			}
		}

		$file = $this->addFileEntry(__DIR__ . '/../../web/images/banner/logo_transparent.png', "$imagesDir/chitanka-logo");
		$epubFile->addFile('logo-image', $file);
	}

	/**
	 * @param string $content
	 * @param string $filename
	 * @param string $cacheKey
	 * @param bool $compress
	 */
	private function addContentEntry($content, $filename, $cacheKey = null, $compress = true) {
		$fEntry = $this->zipFile->newFileEntry($content, $filename, 0, $compress);
		if ($cacheKey) {
			CacheManager::setDlCache($cacheKey, serialize($fEntry));
		}
		$this->zipFile->addFileEntry($fEntry);

		return $filename;
	}

	/**
	 * @param string $filename
	 * @param string $targetName
	 * @return string
	 */
	private function addFileEntry($filename, $targetName = null) {
		if ($targetName) {
			if ($targetName[strlen($targetName)-1] == '/') {
				$targetName .= basename($filename);
			} else {
				$targetName .= '.' . File::getFileExtension($filename);
			}
		} else {
			$targetName = basename($filename);
		}

		$fEntry = $this->zipFile->newFileEntry(file_get_contents($filename), $targetName);
		$this->zipFile->addFileEntry($fEntry);

		return $targetName;
	}

	/**
	 * @param array $textIds
	 * @param string $format
	 * @return string
	 */
	public static function getDlCache($textIds, $format = '') {
		return self::getDlFileByHash( self::getHashForTextIds($textIds, $format) );
	}

	/**
	 * @param array $textIds
	 * @param string $file
	 * @param string $format
	 * @return string
	 */
	public static function setDlCache($textIds, $file, $format = '') {
		$db = Setup::db();
		$pk = self::getHashForTextIds($textIds, $format);
		$db->insert(DBT_DL_CACHE, [
			"id = $pk",
			'file' => $file,
		], true, false);
		foreach ( (array) $textIds as $textId ) {
			$db->insert(DBT_DL_CACHE_TEXT, [
				"dc_id = $pk",
				'text_id' => $textId,
			], true);
		}
		return $file;
	}

	/**
	 * @param string $hash
	 */
	public static function getDlFileByHash($hash) {
		return Setup::db()->getFields(DBT_DL_CACHE, ["id = $hash"], 'file');
	}

	/**
	 * @param array $textIds
	 * @param string $format
	 * @return string
	 */
	private static function getHashForTextIds($textIds, $format = '') {
		if (is_array($textIds)) {
			$textIds = implode(',', $textIds);
		}
		return '0x' . substr(md5($textIds . $format), 0, 16);
	}

	/**
	 * @param string $fname
	 * @return string
	 */
	public static function getDlFile($fname) {
		$file = self::getFullDlFileName($fname);
		if ( file_exists($file) && filesize($file) ) {
			touch($file);
			return $file;
		}
		return null;
	}

	/**
	 * @param string $fname
	 * @param string $fcontent
	 */
	public static function setDlFile($fname, $fcontent) {
		return File::myfile_put_contents(self::getFullDlFileName($fname), $fcontent);
	}

	/**
	 * @param string $filename
	 * @return string
	 */
	public static function getFullDlFileName($filename) {
		return /*BASEDIR .'/'. */self::$dlDir .'/'. $filename;
	}

	/**
	 * @param BaseWork $work
	 * @param string $format
	 * @return string
	 */
	private function createWorkFileName(BaseWork $work, $format = '') {
		$filename = File::cleanFileName( Char::cyr2lat($work->getNameForFile()) );
		$filename = substr($filename, 0, 150);
		if ($format !== '') {
			$filename .= ".$format";
		}
		return $filename;
	}
}
