<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
//umask(0000);

// This check prevents access to debug front controllers that are deployed by accident to production servers.
if (isset($_SERVER['HTTP_CLIENT_IP'])
	|| isset($_SERVER['HTTP_X_FORWARDED_FOR'])
	|| !in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', 'fe80::1', '::1'])
) {
	header('HTTP/1.0 403 Forbidden');
	exit('Only system administrators are allowed to access this file.');
}

$rootDir = __DIR__.'/..';
require $rootDir.'/var/bootstrap.php.cache';
Debug::enable();

require $rootDir.'/app/AppKernel.php';

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
