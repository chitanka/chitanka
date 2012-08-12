<?php
use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';

// Use APC for autoloading to improve performance
// Change 'sf2' by the prefix you want in order to prevent key conflict with another application
// $loader = new ApcClassLoader('sf2', $loader);
// $loader->register(true);

require_once __DIR__.'/../app/AppKernel.php';
require_once __DIR__.'/../app/AppCache.php';

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
