<?php
function isCacheable() {
	return $_SERVER['REQUEST_METHOD'] == 'GET' && !array_key_exists('mlt', $_COOKIE);
}
class Cache {
	private $file;
	private $request;
	private $debug = false;

	public function __construct($requestUri, $cacheDir) {
		$hash = md5($requestUri);
		$this->file = new CacheFile("$cacheDir/$hash[0]/$hash[1]/$hash[2]/$hash");
		$this->request = $requestUri;
	}

	public function get() {
		if ( ! $this->file->exists()) {
			return null;
		}
		$ttl = $this->file->getRemainingTtl();
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
	public function set($content, $ttl) {
		if ( ! $ttl) {
			return;
		}
		$this->file->write($content);
		$this->file->setTtl($ttl);
		$this->log("+++ CACHE MISS ($ttl)");
	}
	private function purge() {
		$this->file->delete();
		$this->log('--- CACHE PURGE');
	}
	private function log($msg) {
		if ($this->debug) {
			error_log("$msg - $this->request");
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
	public function write($content) {
		$dir = dirname($this->name);
		if ( ! file_exists($dir)) {
			mkdir($dir, 0777, true);
		}
		file_put_contents($this->name, gzdeflate($content));
	}
	public function read() {
		$content = file_get_contents($this->name);
		if (empty($content) || $content[0] == '<') { // not compressed
			return $content;
		}
		return gzinflate($content);
	}
	public function delete() {
		unlink($this->name);
		unlink("$this->name.ttl");
	}
	public function setTtl($value) {
		file_put_contents("$this->name.ttl", $value);
	}
	public function getTtl() {
		return file_get_contents("$this->name.ttl");
	}
	public function getRemainingTtl() {
		$origTtl = $this->getTtl() + rand(0, 30) /* guard for race conditions */;
		return $origTtl - $this->getAge();
	}
	public function getAge() {
		return time() - filemtime($this->name);
	}
}

$cache = new Cache($_SERVER['REQUEST_URI'], __DIR__.'/../app/cache/prod/simple_http_cache');

if (isCacheable() && null !== ($cachedContent = $cache->get())) {
	header("Cache-Control: public, max-age=".$cachedContent['ttl']);
	echo $cachedContent['data'];
	return;
}

use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;

$rootDir = __DIR__.'/..';
require_once $rootDir.'/app/bootstrap.php.cache';

try {
	// Use APC for autoloading to improve performance
	$loader = new ApcClassLoader('chitanka', $loader);
	$loader->register(true);
} catch (\RuntimeException $e) {
	// APC not enabled
}

require_once $rootDir.'/app/AppKernel.php';
//require_once $rootDir.'/app/AppCache.php';

register_shutdown_function(function(){
	$error = error_get_last();
	if ($error['type'] == E_ERROR) {
		if (preg_match('/parameters\.yml.+does not exist/', $error['message'])) {
			header('Location: /install.php');
			exit;
		}
		ob_clean();
		header('HTTP/1.1 503 Service Unavailable');
		readfile(__DIR__ . '/503.html');
	}
});

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
//$kernel = new AppCache($kernel);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
if (isCacheable() && $response->isOk()) {
	try {
		$cache->set($response->getContent(), $response->getTtl());
	} catch (\RuntimeException $e) {
	}
}
$response->send();
$kernel->terminate($request, $response);
