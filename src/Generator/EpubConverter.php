<?php namespace App\Generator;

use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;

class EpubConverter {

	/** @var array */
	private $parameters;
	/** @var string */
	private $cacheDir;

	public function __construct(array $parameters, string $cacheDir) {
		$this->parameters = $parameters;
		$this->cacheDir = $cacheDir;
	}

	public function convert(string $epubUrl, string $targetFormat): string {
		$this->assertUrl($epubUrl);
		$this->assertSupportedTargetFormat($targetFormat);
		$this->assertEnabledTargetFormat($targetFormat);

		$commandTemplate = $this->parameters["{$targetFormat}_command"];
		if (empty($commandTemplate)) {
			throw new InvalidArgumentException("The target format '{$targetFormat}' does not have a shell converter command.");
		}

		$cachedOutputFileStore = new CachedOutputFileStore($this->cacheDir, $epubUrl);
		$cachedOutputFile = $cachedOutputFileStore->get($targetFormat);
		if ($cachedOutputFile) {
			return $cachedOutputFile;
		}

		$storedEpubFile = $cachedOutputFileStore->get('epub');
		if (!$storedEpubFile) {
			$epubFile = $this->downloadEpub($epubUrl);
			$epubFile->saveAt($this->cacheDir);
			$storedEpubFile = $epubFile->path;
		}

		$outputFile = $this->convertFile($commandTemplate, $storedEpubFile, $targetFormat);
		$cachedOutputFileStore->set($outputFile);
		return $outputFile;
	}

	private function convertFile(string $commandTemplate, string $inputFile, string $outputFormat): string {
		$outputFile = str_replace('.epub', ".$outputFormat", $inputFile);
		$command = strtr($commandTemplate, [
			'INPUT_FILE' => escapeshellarg($inputFile),
			'OUTPUT_FILE' => escapeshellarg($outputFile),
			'OUTPUT_FILE_BASENAME' => escapeshellarg(basename($outputFile)),
		]);
		$binDir = realpath(__DIR__.'/../../bin');
		chdir($binDir);// go to local bin directory to allow execution of locally stored binaries
		$execPath = getenv('PATH');
		$extendPath = $execPath ? 'PATH=.:$PATH' : '';
		$commandWithCustomPath = trim("$extendPath $command");
		shell_exec($commandWithCustomPath);
		return $outputFile;
	}

	private function downloadEpub(string $epubUrl) {
		$stream = fopen($this->sanitizeSource($epubUrl), 'r');
		$headers = stream_get_meta_data($stream)['wrapper_data'];
		$contents = stream_get_contents($stream);
		fclose($stream);

		$epubFile = $this->getFileNameFromHeaders($headers) ?: basename($epubUrl);
		return new DownloadedFile($epubFile, $contents);
	}

	/*
	 * Example headers:
	 *     - Location: /cache/dl/file.epub
	 *     - Content-Disposition: attachment; filename="file.epub"
	 */
	private function getFileNameFromHeaders(array $headers): string {
		foreach (array_reverse($headers) as $header) {
			$parts = explode(':', $header);
			$name = strtolower(trim($parts[0]));
			switch ($name) {
				case 'content-disposition':
					$normalizedValue = strtr($parts[1], [' ' => '', '"' => '', "'" => '']) . ';';
					if (preg_match('#filename=([^;]+)#', $normalizedValue, $matches)) {
						return basename($matches[1]);
					}
					return '';
				case 'location':
					return basename(trim($parts[1]));
			}
		}
		return '';
	}

	private function assertUrl(string $urlToAssert) {
		if ( ! preg_match('#^https?://#', $urlToAssert)) {
			throw new InvalidArgumentException("Not a valid URL: '{$urlToAssert}'");
		}
	}

	private function assertSupportedTargetFormat(string $targetFormat) {
		$key = "{$targetFormat}_enabled";
		if ( ! isset($this->parameters[$key])) {
			throw new InvalidArgumentException("Unsupported target format: '{$targetFormat}'");
		}
	}

	private function assertEnabledTargetFormat(string $targetFormat) {
		$key = "{$targetFormat}_enabled";
		if ( ! $this->parameters[$key]) {
			throw new InvalidArgumentException("Target format is not enabled: '{$targetFormat}'");
		}
	}

	private function sanitizeSource(string $source): string {
		return preg_replace('#[^a-zA-Z\d:/.,_-]#', '', $source);
	}
}

class DownloadedFile {
	public $name;
	public $contents;
	public $path;

	public function __construct($name, $contents = '') {
		$this->name = $name;
		$this->contents = $contents;
	}

	public function saveAt(string $directory) {
		$this->path = "$directory/$this->name";
		$fs = new Filesystem();
		$fs->dumpFile($this->path, $this->contents);
	}
}

class CachedOutputFileStore {
	private $cacheDir;
	private $store;
	private $fs;

	public function __construct(string $cacheDir, string $sourceUrl) {
		$this->cacheDir = $cacheDir;
		$this->store = "$cacheDir/output-".md5($sourceUrl).'.file';
		$this->fs = new Filesystem();
	}

	public function get(string $outputFormat): ?string {
		if ( ! $this->fs->exists($this->store)) {
			return null;
		}
		$cachedOutputFile = "$this->cacheDir/". trim(file_get_contents($this->store)) .".$outputFormat";
		if ( ! $this->fs->exists($cachedOutputFile)) {
			return null;
		}
		return $cachedOutputFile;
	}

	public function set(string $outputFile) {
		$baseNameWoExtension = basename(str_replace(strrchr($outputFile, '.'), '', $outputFile));
		$this->fs->dumpFile($this->store, $baseNameWoExtension);
	}
}
