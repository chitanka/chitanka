<?php

require_once __DIR__.'/../app/bootstrap.php.cache';
require_once __DIR__.'/../app/AppKernel.php';
require_once __DIR__.'/../app/AppCache.php';

use Symfony\Component\HttpFoundation\Request;

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
$kernel->handle(Request::createFromGlobals())->send();
