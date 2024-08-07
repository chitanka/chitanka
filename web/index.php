<?php
function exitWithMessage($template = 'error', $retryAfter = 300) {
	header('HTTP/1.0 503 Service Temporarily Unavailable');
	header('Status: 503 Service Temporarily Unavailable');
	header("Retry-After: $retryAfter");
	readfile(__DIR__ . "/$template.html");
	exit;
}
function isCacheable() {
	return filter_input(INPUT_SERVER, 'CACHE_ENABLE') && $_SERVER['REQUEST_METHOD'] == 'GET' && !isset($_COOKIE['mlt']);
}
class Cache {
	private $file;
	private $request;
	private $debug = false;
	private $logFile;

	public function __construct($requestUri, $cacheDir, $logDir = '', $compressCache = true) {
		$hash = md5($requestUri);
		$this->file = new CacheFile("$cacheDir/$hash[0]/$hash[1]/$hash[2]/$hash", $compressCache);
		$this->request = $requestUri;
		$this->logFile = "$logDir/cache.log";
	}

	public function get() {
		if ( ! $this->file->exists()) {
			return null;
		}
		$ttl = $this->file->getTtl();
		if ($ttl <= 0) {
			$this->purge();
			return null;
		}
		$this->log("=== HIT");
		return [
			'data' => $this->file->read(),
			'ttl' => $ttl,
		];
	}
	/**
	 * Set cache content with a given time to live.
	 * @param string $content
	 * @param int $ttl Time to live (in seconds)
	 */
	public function set($content, $ttl) {
		if (!$ttl) {
			$this->log("/// SKIP");
			return;
		}
		$this->file->write($content, $ttl);
		$this->log("+++ MISS ($ttl)");
	}
	private function purge() {
		$this->file->delete();
		$this->log('--- PURGE');
	}
	private function log($msg) {
		if ($this->debug) {
			file_put_contents($this->logFile, "$msg - $this->request\n", FILE_APPEND);
		}
	}
}
class CacheFile {
	private $name;
	private $compressed = true;

	public function __construct($name, $compresed = true) {
		$this->name = $name;
		$this->compressed = $compresed;
	}
	public function exists() {
		return file_exists($this->name);
	}

	/**
	 * @param string $content
	 * @param integer $ttl
	 */
	public function write($content, $ttl) {
		if ( ! file_exists($dir = dirname($this->name))) {
			mkdir($dir, 0777, true);
		}
		$content = ltrim($content);
		file_put_contents($this->name, $this->compressed ? gzdeflate($content) : $content);
		$this->setTtl($ttl);
	}
	public function read() {
		$content = file_get_contents($this->name);
		if (empty($content)) {
			return $content;
		}
		return $this->compressed ? gzinflate($content) : $content;
	}
	public function delete() {
		unlink($this->name);
	}
	/**
	 * The time to live is set implicitly through the last modification time, e.g.
	 * if a file has TTL of 1 hour, its modification time is set to 1 hour in the future
	 * @param integer $ttl
	 */
	private function setTtl($ttl) {
		touch($this->name, time() + $ttl);
	}
	public function getTtl() {
		return filemtime($this->name) - time()
			+ rand(0, 30) /* guard for race conditions */;
	}
}

if (isCacheable()) {
	$requestUri = filter_input(INPUT_SERVER, 'REQUEST_URI');
	if (filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest') {
		$requestUri .= '.ajax';
	}
	$compressCache = !filter_input(INPUT_SERVER, 'CACHE_NOCOMPRESS');
	$varDir = __DIR__.'/../var';
	$cache = new Cache($requestUri, "$varDir/cache/simple_http_cache", "$varDir/log", $compressCache);
	if (null !== ($cachedContent = $cache->get())) {
		header("Cache-Control: public, max-age=".$cachedContent['ttl']);
		echo $cachedContent['data'];
		return;
	}
}

// uncomment to enter maintenance mode
// DO NOT remove next line - it is used by the auto-update command
//exitWithMessage('maintenance');

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload.php';

// allow generated files (cache, logs) to be world-writable
umask(0000);

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG']) {
	Debug::enable();
}

$kernel = new App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
if (isset($cache) && $response->isOk()) {
	try {
		$cache->set($response->getContent(), $response->getTtl());
	} catch (\RuntimeException $e) {
		// do nothing for now; possibly log it in the future
	}
}
$response->send();
$kernel->terminate($request, $response);
