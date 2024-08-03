<?php
$allowedDomains = [
	'sfbg.us', 'www.sfbg.us',
];

if (isset($_GET['url'])) {
	$url = urlencode($_GET['url']);
	$url = strtr($url, [
		'%3A' => ':',
		'%2F' => '/',
		'%3F' => '?',
		'%3D' => '=',
		'%26' => '&',
	]);

	if (strpos($url, 'http://') !== 0) {
		die('not an http address');
	}
	if ( ! in_array(parse_url($url, PHP_URL_HOST), $allowedDomains)) {
		die('not allowed domain');
	}
	$response = @file_get_contents($url);
	if ($response === false) {
		echo 'fetch error';
	} else {
		echo $response;
	}
}
