<?php
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
require_once $rootDir.'/app/AppCache.php';

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
$kernel = new AppCache($kernel);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
