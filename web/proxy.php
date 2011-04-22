<?php
if (isset($_GET['url'])) {
	$url = urlencode($_GET['url']);
	$url = strtr($url, array(
		'%3A' => ':',
		'%2F' => '/',
		'%3F' => '?',
		'%3D' => '=',
		'%26' => '&',
	));

	$response = @file_get_contents($url);
	if ($response === false) {
		echo 'error';
	} else {
		echo $response;
	}
}
