<?php
namespace Chitanka\LibBundle\Legacy;

use Chitanka\LibBundle\Util\Char;
use Chitanka\LibBundle\Util\File;
use Chitanka\LibBundle\Entity\Text;
use Chitanka\LibBundle\Entity\BaseWork;

class DownloadFile
{

	static private $_dlDir = 'cache/dl';
	private $_zipFile = null;


	public function __construct()
	{
		$this->_zipFile = new ZipFile;
	}

	public function getSfbForBook($book)
	{
		return $this->getDlFileForBook($book, 'sfb', 'addBinariesForSfb');
	}

	protected function addBinariesForSfb($book, $filename)
	{
		if ( ($cover = $book->getCover()) ) {
			$this->addFileEntry($cover, $filename);
		}
		foreach ($book->getImages() as $image) {
			$this->addFileEntry($image);
		}
	}


	public function getTxtForBook($book)
	{
		return $this->getDlFileForBook($book, 'txt');
	}


	public function getFb2ForBook($book)
	{
		return $this->getDlFileForBook($book, 'fb2');
	}

	public function getEpubForBook($book)
	{
		return $this->getDlFileForBook($book, 'epub', 'addEpubEntries');
	}

	public function getEpubForText($text)
	{
		return $this->getDlFileForText($text, 'epub', 'addEpubEntries');
	}

	public function getEpubForTexts($texts)
	{
		return $this->getDlFileForTexts($texts, 'epub', 'addEpubEntries');
	}

	/**
	* @param Book    $book
	* @param string  $format
	* @param string  $binaryCallback
	*/
	public function getDlFileForBook($book, $format, $binaryCallback = null)
	{
		$textIds = $book->getTextIds();

		if ( ($dlCache = self::getDlCache($textIds, $format)) ) {
			if ( ($dlFile = self::getDlFile($dlCache)) ) {
				return $dlFile;
			}
		}

		$filename = File::cleanFileName( Char::cyr2lat($book->getNameForFile()) );

		$getMethod = sprintf('getContentAs%s', ucfirst($format));
		if ( method_exists($book, $getMethod) ) {
			$cacheKey = "book-$book->id.$format";
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
		self::setDlFile($zipFilename, $this->_zipFile->file());

		return self::getDlFile($zipFilename);
	}


	public function getDlFileForTexts($texts, $format, $binaryCallback = null)
	{
		if ( ($dlCache = self::getDlCache($texts, $format)) ) {
			if ( ($dlFile = self::getDlFile($dlCache)) ) {
				return $dlFile;
			}
		}

		foreach ($texts as $textId) {
			if ( ($text = Text::newFromId($textId)) ) {
				$dlFile = new DownloadFile;
				$filename = $dlFile->getDlFileForText($text, $format, $binaryCallback);
				$this->_zipFile->addNewFileEntry(file_get_contents($filename), basename($filename), 0, false);
			}
		}

		$zipFilename = sprintf('chitanka-info-%d-files-%s-%s.zip', count($texts), uniqid(), $format);
		// TODO
		self::setDlCache($textIds, $zipFilename, $format);
		self::setDlFile($zipFilename, $this->_zipFile->file());

		return self::getDlFile($zipFilename);
	}

	/**
	*
	*/
	public function getDlFileForText($text, $format, $binaryCallback = null)
	{
		$textIds = array($text->id);

		if ( ($dlCache = self::getDlCache($textIds, $format)) ) {
			if ( ($dlFile = self::getDlFile($dlCache)) ) {
				return $dlFile;
			}
		}

		$filename = File::cleanFileName( Char::cyr2lat($text->getNameForFile()) );

		$getMethod = sprintf('getContentAs%s', ucfirst($format));
		if ( method_exists($text, $getMethod) ) {
			$cacheKey = "text-$text->id.$format";
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
		self::setDlFile($zipFilename, $this->_zipFile->file());

		return self::getDlFile($zipFilename);
	}



	protected function addEpubEntries($work, $filename)
	{
		$epubFile = new EpubFile($work);

		$file = $epubFile->getMimetypeFile();
		$this->addContentEntry($file['content'], $file['name'], null, false);

		$file = $epubFile->getContainerFile();
		$this->addContentEntry($file['content'], $file['name']);

		$file = $epubFile->getCssFile();
		$this->addContentEntry($file['content'], $file['name']);

		$this->addCoverForEpub($work, $epubFile);
		$this->addBackCoverForEpub($work, $epubFile);

		$file = $epubFile->getTitlePageFile();
		$this->addContentEntry($file['content'], $file['name']);
		$epubFile->addItem($file['name'], $file['title'], 'pre');

		$this->addAnnotationForEpub($work, $epubFile);
		$this->addExtraInfoForEpub($work, $epubFile);
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


	protected function addAnnotationForEpub($work, $epubFile)
	{
		if ( ($file = $epubFile->getAnnotation()) ) {
			$this->addContentEntry($file['content'], $file['name']);
			$epubFile->addItem($file['name'], $file['title'], 'pre');
		}
	}

	protected function addExtraInfoForEpub($work, $epubFile)
	{
		if ( ($file = $epubFile->getExtraInfo()) ) {
			$this->addContentEntry($file['content'], $file['name']);
			$epubFile->addItem($file['name'], $file['title'], 'post');
		}
	}


	protected function addChaptersForEpub($work, $epubFile)
	{
		$curObjCount = \Sfblib_SfbConverter::getObjectCount();
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

	protected function addCoverForEpub($work, $epubFile)
	{
		if ( ($cover = $work->getCover(400)) ) {
			$file = $this->addFileEntry($cover, $epubFile->getCoverFileName());
			$epubFile->addCover($file);
		}
		if ( ($file = $epubFile->getCoverPageFile()) ) {
			$this->addContentEntry($file['content'], $file['name']);
			$epubFile->addItem($file['name'], $file['title'], 'pre');
		}
	}

	protected function addBackCoverForEpub($work, $epubFile)
	{
		if ( ($cover = $work->getBackCover(400)) ) {
			$file = $this->addFileEntry($cover, $epubFile->getBackCoverFileName());
			$epubFile->addBackCover($file);
		}
		if ( ($file = $epubFile->getBackCoverPageFile()) ) {
			$this->addContentEntry($file['content'], $file['name']);
			$epubFile->addItem($file['name'], $file['title'], 'post');
		}
	}

	protected function addImagesForEpub($work, $epubFile)
	{
		$imagesDir = $epubFile->getImagesDir();

		$thumbs = array();
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

		$file = $this->addFileEntry(BASEDIR . '/img/logo_transparent.png', "$imagesDir/chitanka-logo");
		$epubFile->addFile('logo-image', $file);
	}



	protected function addContentEntry($content, $filename, $cacheKey = null, $compress = true)
	{
		$fEntry = $this->_zipFile->newFileEntry($content, $filename, 0, $compress);
		if ($cacheKey) {
			CacheManager::setDlCache($cacheKey, serialize($fEntry));
		}
		$this->_zipFile->addFileEntry($fEntry);

		return $filename;
	}

	protected function addFileEntry($filename, $targetName = null)
	{
		if ($targetName) {
			if ($targetName[strlen($targetName)-1] == '/') {
				$targetName .= basename($filename);
			} else {
				$targetName .= '.' . File::getFileExtension($filename);
			}
		} else {
			$targetName = basename($filename);
		}

		$fEntry = $this->_zipFile->newFileEntry(file_get_contents($filename), $targetName);
		$this->_zipFile->addFileEntry($fEntry);

		return $targetName;
	}



	static public function getDlCache($textIds, $format = '')
	{
		return self::getDlFileByHash( self::getHashForTextIds($textIds, $format) );
	}

	static public function setDlCache($textIds, $file, $format = '')
	{
		$db = Setup::db();
		$pk = self::getHashForTextIds($textIds, $format);
		$db->insert(DBT_DL_CACHE, array(
			"id = $pk",
			'file' => $file,
		), true, false);
		foreach ( (array) $textIds as $textId ) {
			$db->insert(DBT_DL_CACHE_TEXT, array(
				"dc_id = $pk",
				'text_id' => $textId,
			), true);
		}
		return $file;
	}


	static public function getDlFileByHash($hash)
	{
		return Setup::db()->getFields(DBT_DL_CACHE, array("id = $hash"), 'file');
	}

	static protected function getHashForTextIds($textIds, $format = '')
	{
		if ( is_array($textIds) ) {
			$textIds = implode(',', $textIds);
		}
		return '0x' . substr(md5($textIds . $format), 0, 16);
	}



	static public function getDlFile($fname)
	{
		$file = self::getFullDlFileName($fname);
		if ( file_exists($file) && filesize($file) ) {
			touch($file);
			return $file;
		}

		return null;
	}


	static public function setDlFile($fname, $fcontent)
	{
		return File::myfile_put_contents(self::getFullDlFileName($fname), $fcontent);
	}


	static public function getFullDlFileName($filename)
	{
		return /*BASEDIR .'/'. */self::$_dlDir .'/'. $filename;
	}





	protected function addSfbToDlFileFromCache($textId)
	{
		$fEntry = unserialize( CacheManager::getDlCache($textId), '.sfb' );
		$this->_zipFile->addFileEntry($fEntry);

		return $fEntry['name'];
	}

	protected function addSfbToDlFileFromNew($textId)
	{
		$mainFileData = $this->getMainFileData($textId);
		if ( ! $mainFileData ) {
			return false;
		}
		list($this->filename, $this->fPrefix, $this->fSuffix, $fbi) = $mainFileData;
		$this->addTextFileEntry($textId, '.sfb');
		if ( $this->withFbi ) {
			$this->addMiscFileEntry($fbi, $textId, '.fbi');
		}
		return true;
	}

	protected function addBinaryFileEntries($textId, $filename) {
		// add covers
		if ( $this->withCover ) {
			foreach (BaseWork::getCovers($textId) as $file) {
				$ename = BaseWork::renameCover(basename($file), $filename);
				$fEntry = $this->_zipFile->newFileEntry(file_get_contents($file), $ename);
				$this->_zipFile->addFileEntry($fEntry);
			}
		}

		// add images
		$dir = Legacy::getContentFilePath('img', $textId);
		if ( !is_dir($dir) ) { return; }
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				$fullname = "$dir/$file";
				if ( $file[0] == '.' || $file[0] == '_' ||
					File::isArchive($file) || is_dir($fullname) ) { continue; }
				$fEntry = $this->_zipFile->newFileEntry(file_get_contents($fullname), $file);
				$this->_zipFile->addFileEntry($fEntry);
			}
			closedir($dh);
		}
	}

}
