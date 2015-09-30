<?php namespace App\Generator;

use App\Entity\Text;
use App\Entity\TextRepository;
use App\Legacy\CacheManager;
use App\Service\ContentService;
use App\Util\Char;
use App\Util\File;

class TextDownloadService {

	private $textRepo;
	private $textIds;
	private $zf;
	private $zipFileName;
	private $filename;
	private $fPrefix;
	private $fSuffix;
	private $work;
	private $withFbi;
	// track here how many times a filename occurs
	private $_fnameCount = [];

	public function __construct(TextRepository $textRepo) {
		$this->textRepo = $textRepo;
	}

	public function generateFile($ids, $format, $requestedFilename) {
		switch ($format) {
			case 'txt.zip':
				return $this->getTxtZipFile($ids, $requestedFilename);
			case 'fb2.zip':
				return $this->getFb2ZipFile($ids, $requestedFilename);
			case 'sfb.zip':
				return $this->getSfbZipFile($ids, $requestedFilename);
			case 'epub':
				return $this->getEpubFile($ids);
		}
		return null;
	}

	private function getTxtZipFile($id, $requestedFilename) {
		$this->initZipData($id, $requestedFilename);
		return $this->createTxtDlFile();
	}

	private function getSfbZipFile($id, $requestedFilename) {
		$this->initZipData($id, $requestedFilename);
		return $this->createSfbDlFile();
	}

	private function getFb2ZipFile($id, $requestedFilename) {
		$this->initZipData($id, $requestedFilename);
		return $this->createFb2DlFile();
	}

	private function getEpubFile($textIds) {
		$dlFile = new DownloadFile;
		if (count($textIds) > 1) {
			return $dlFile->getEpubForTexts($this->textRepo->findBy(['id' => $textIds]));
		}
		$text = $this->textRepo->find($textIds[0]);
		if ($text) {
			return $dlFile->getEpubForText($text);
		}
		return null;
	}

	/** Sfb */
	private function createSfbDlFile() {
		$key = '';
		$key .= '-sfb';
		return $this->createDlFile($this->textIds, 'sfb', $key);
	}

	private function addSfbToDlFileFromCache($textId) {
		$fEntry = unserialize(CacheManager::getDlCache($textId, '.sfb'));
		$this->zf->addFileEntry($fEntry);
		if ( $this->withFbi ) {
			$this->zf->addFileEntry(unserialize(CacheManager::getDlCache($textId, '.fbi')));
		}
		$this->filename = $this->rmFEntrySuffix($fEntry['name']);
		return true;
	}

	private function addSfbToDlFileFromNew($textId) {
		$mainFileData = $this->getMainFileData($textId);
		if ( ! $mainFileData ) {
			return false;
		}
		list($this->filename, $this->fPrefix, $this->fSuffix, $fbi) = $mainFileData;
		$this->addTextFileEntry($textId, '.sfb');
//		if ( $this->withFbi ) {
//			$this->addMiscFileEntry($fbi, $textId, '.fbi');
//		}
		return true;
	}

	private function addSfbToDlFileEnd($textId) {
		$this->addBinaryFileEntries($textId);
		return true;
	}

	/** Fb2 */
	private function createFb2DlFile() {
		return $this->createDlFile($this->textIds, 'fb2');
	}

	private function addFb2ToDlFileFromCache($textId) {
		$fEntry = unserialize(CacheManager::getDlCache($textId, '.fb2'));
		$this->zf->addFileEntry($fEntry);
		$this->filename = $this->rmFEntrySuffix($fEntry['name']);
		return true;
	}

	private function addFb2ToDlFileFromNew($textId) {
		$work = $this->textRepo->find($textId);
		if ( ! $work ) {
			return false;
		}
		$this->filename = $this->getFileName($work);
		$this->addMiscFileEntry($work->getContentAsFb2(), $textId, '.fb2');
		return true;
	}

	/** Txt */
	private function createTxtDlFile() {
		return $this->createDlFile($this->textIds, 'txt');
	}

	private function addTxtToDlFileFromCache($textId) {
		$fEntry = unserialize(CacheManager::getDlCache($textId, '.txt'));
		$this->zf->addFileEntry($fEntry);
		$this->filename = $this->rmFEntrySuffix($fEntry['name']);
		return true;
	}

	private function addTxtToDlFileFromNew($textId) {
		$work = $this->textRepo->find($textId);
		if ( ! $work ) {
			return false;
		}
		$this->filename = $this->getFileName($work);
		$this->addMiscFileEntry($work->getContentAsTxt(), $textId, '.txt');
		return true;
	}

	/** Common */
	private function createDlFile($textIds, $format, $dlkey = null) {
		$textIds = $this->normalizeTextIds($textIds);
		if ($dlkey === null) {
			$dlkey = ".$format";
		}

		$dlCache = CacheManager::getDl($textIds, $dlkey);
		if ( $dlCache ) {
			$dlFile = CacheManager::getDlFile($dlCache);
			if ( file_exists($dlFile) && filesize($dlFile) ) {
				return $dlFile;
			}
		}

		$fileCount = count($textIds);
		$setZipFileName = $fileCount == 1 && empty($this->zipFileName);

		foreach ($textIds as $textId) {
			$method = 'add' . ucfirst($format) . 'ToDlFileFrom';
			$method .= CacheManager::dlCacheExists($textId, ".$format") ? 'Cache' : 'New';
			if ( ! $this->$method($textId) ) {
				$fileCount--; // no file was added
				continue;
			}
			$sharedMethod = 'add' . ucfirst($format) . 'ToDlFileEnd';
			if ( method_exists($this, $sharedMethod) ) {
				$this->$sharedMethod($textId);
			}
			if ($setZipFileName) {
				$this->zipFileName = $this->filename;
			}
		}
		if ( $fileCount < 1 ) {
			throw new \Exception('Не е посочен валиден номер на текст за сваляне!');
		}

		if ( ! $setZipFileName && empty($this->zipFileName) ) {
			$this->zipFileName = "Архив от Моята библиотека - $fileCount файла-".time();
		}

		$this->zipFileName .= $fileCount > 1 ? "-$format" : $dlkey;
		$this->zipFileName = File::cleanFileName( Char::cyr2lat($this->zipFileName) );
		$fullZipFileName = $this->zipFileName . '.zip';

		CacheManager::setDlFile($fullZipFileName, $this->zf->file());
		CacheManager::setDl($textIds, $fullZipFileName, $dlkey);
		return CacheManager::getDlFile($fullZipFileName);
	}

	private function normalizeTextIds($textIds) {
		foreach ($textIds as $key => $textId) {
			if ( ! $textId || ! is_numeric($textId) ) {
				unset($textIds[$key]);
			}
		}
		sort($textIds);
		$textIds = array_unique($textIds);
		return $textIds;
	}

	private function addTextFileEntry($textId, $suffix = '.txt') {
		$fEntry = $this->zf->newFileEntry($this->fPrefix .
			$this->getContentData($textId) .
			$this->fSuffix, $this->filename . $suffix);
		CacheManager::setDlCache($textId, serialize($fEntry));
		$this->zf->addFileEntry($fEntry);
	}

	private function addMiscFileEntry($content, $textId, $suffix = '.txt') {
		$fEntry = $this->zf->newFileEntry($content, $this->filename . $suffix);
		CacheManager::setDlCache($textId, serialize($fEntry), $suffix);
		$this->zf->addFileEntry($fEntry);
	}

	private function addBinaryFileEntries($textId) {
		// add images
		$dir = ContentService::getContentFilePath('img', $textId);
		if ( !is_dir($dir) ) { return; }
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				$fullname = "$dir/$file";
				if ( $file[0] == '.' || $file[0] == '_' ||
					File::isArchive($file) || is_dir($fullname) ) { continue; }
				$fEntry = $this->zf->newFileEntry(file_get_contents($fullname), $file);
				$this->zf->addFileEntry($fEntry);
			}
			closedir($dh);
		}
	}

	private function getContentData($textId) {
		$fname = ContentService::getContentFilePath('text', $textId);
		if ( file_exists($fname) ) {
			return file_get_contents($fname);
		}
		return '';
	}

	private function getMainFileData($textId) {
		$work = $this->textRepo->find($textId);
		return [
			$this->getFileName($work),
			$this->getFileDataPrefix($work),
			$this->getFileDataSuffix($work),
			$this->getTextFileStart() . $work->getFbi()
		];
	}

	public function getFileName($work = null) {
		if ( is_null($work) ) $work = $this->work;

		return $this->getUniqueFileName($work->getNameForFile());
	}

	private function getUniqueFileName($filename) {
		if ( isset( $this->_fnameCount[$filename] ) ) {
			$this->_fnameCount[$filename]++;
			$filename .= $this->_fnameCount[$filename];
		} else {
			$this->_fnameCount[$filename] = 1;
		}
		return $filename;
	}

	private function initZipData($textId, $requestedFilename = null) {
		$this->textIds = $textId;
		$this->zf = new ZipFile;
		if ($requestedFilename) {
			$this->zipFileName = "chitanka-$requestedFilename";
		}
		$this->_fnameCount = [];
	}

	public function getFileDataPrefix(Text $work) {
		$prefix = $this->getTextFileStart()
			. "|\t" . $work->getAuthorNamesString() . "\n"
			. $work->getTitleAsSfb() . "\n\n\n";
		$anno = $work->getAnnotation();
		if ( !empty($anno) ) {
			$prefix .= "A>\n$anno\nA$\n\n\n";
		}
		return $prefix;
	}

	public function getFileDataSuffix(Text $work) {
		$suffix = "\n"
			. "I>\n"
			. $work->getExtraInfoForDownload()
			. "I$\n";
		$suffix = preg_replace('/\n\n+/', "\n\n", $suffix);
		return $suffix;
	}

	public static function getTextFileStart() {
		return "\xEF\xBB\xBF";
	}

	private function rmFEntrySuffix($fEntryName) {
		return preg_replace('/\.[^.]+$/', '', $fEntryName);
	}
}
