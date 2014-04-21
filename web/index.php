<?php
function exitWithMessage($template = 'error', $retryAfter = 300) {
	header('HTTP/1.0 503 Service Temporarily Unavailable');
	header('Status: 503 Service Temporarily Unavailable');
	header("Retry-After: $retryAfter");
	readfile(__DIR__ . "/$template.html");
	exit;
}

function isCacheable() {
	return $_SERVER['REQUEST_METHOD'] == 'GET' && !array_key_exists('mlt', $_COOKIE);
}
class Cache {
	private $file;
	private $request;
	private $debug = false;
	private $logFile;

	public function __construct($requestUri, $cacheDir, $logDir = '') {
		$hash = md5($requestUri);
		$this->file = new CacheFile("$cacheDir/$hash[0]/$hash[1]/$hash[2]/$hash");
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
		$this->log("=== CACHE HIT");
		return array(
			'data' => $this->file->read(),
			'ttl' => $ttl,
		);
	}
	/**
	 * Set cache content with a given time to live.
	 * @param string $content
	 * @param int $ttl Time to live (in seconds)
	 */
	public function set($content, $ttl) {
		if (!$ttl) {
			$this->log("/// CACHE SKIP");
			return;
		}
		$this->file->write($content, $ttl);
		$this->log("+++ CACHE MISS ($ttl)");
	}
	private function purge() {
		$this->file->delete();
		$this->log('--- CACHE PURGE');
	}
	private function log($msg) {
		if ($this->debug) {
			file_put_contents($this->logFile, "$msg - $this->request\n", FILE_APPEND);
		}
	}
}
class CacheFile {
	private $name;

	public function __construct($name) {
		$this->name = $name;
	}
	public function exists() {
		return file_exists($this->name);
	}
	public function write($content, $ttl) {
		if ( ! file_exists($dir = dirname($this->name))) {
			mkdir($dir, 0777, true);
		}
		file_put_contents($this->name, gzdeflate(ltrim($content)));
		$this->setTtl($ttl);
	}
	public function read() {
		$content = file_get_contents($this->name);
		if (empty($content) || $content[0] == '<'/* not compressed */) {
			return $content;
		}
		return gzinflate($content);
	}
	public function delete() {
		unlink($this->name);
	}
	/**
	 * The time to live is set implicitly through the last modification time, e.g.
	 * if a file has TTL of 1 hour, its modification time is set to 1 hour in the future
	 */
	private function setTtl($ttl) {
		touch($this->name, time() + $ttl);
	}
	public function getTtl() {
		return filemtime($this->name) - time()
			+ rand(0, 30) /* guard for race conditions */;
	}
}

$isCacheable = isCacheable();
if ($isCacheable) {
	$requestUri = $_SERVER['REQUEST_URI'];
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
		$requestUri .= '.ajax';
	}
	$cache = new Cache($requestUri, __DIR__.'/../app/cache/simple_http_cache', __DIR__.'/../app/logs');
	if (null !== ($cachedContent = $cache->get())) {
		header("Cache-Control: public, max-age=".$cachedContent['ttl']);
		echo $cachedContent['data'];
		return;
	}
}

// uncomment to enter maintenance mode
// DO NOT remove next line - it is used by the auto-update command
//exitWithMessage('maintenance');

use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;

// allow generated files (cache, logs) to be world-writable
umask(0000);

$rootDir = __DIR__.'/..';
$loader = require $rootDir.'/app/bootstrap.php.cache';

try {
	// Use APC for autoloading to improve performance
	$apcLoader = new ApcClassLoader('chitanka', $loader);
	$loader->unregister();
	$apcLoader->register(true);
} catch (\RuntimeException $e) {
	// APC not enabled
}

require $rootDir.'/app/AppKernel.php';
//require $rootDir.'/app/AppCache.php';

register_shutdown_function(function(){
	$error = error_get_last();
	if ($error['type'] == E_ERROR) {
		if (preg_match('/parameters\.yml.+does not exist/', $error['message'])) {
			header('Location: install.php');
			exit;
		}
		ob_clean();
		exitWithMessage('error');
	}
});

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
//$kernel = new AppCache($kernel);

// When using the HttpCache, we need to call the method explicitly instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
if ($isCacheable && $response->isOk()) {
	try {
		$cache->set($response->getContent(), $response->getTtl());
	} catch (\RuntimeException $e) {
	}
}
$response->send();
$kernel->terminate($request, $response);
