<?php
/**
 * This script serves a CSS file for a given skin.
 *
 * An example request would look like "index.php?skin=orange&menu=right&v=666"
 */
function get($name, $allowedValues) {
	if (isset($_GET[$name]) && in_array($_GET[$name], $allowedValues)) {
		return $_GET[$name];
	}
	return $allowedValues[0];
}

function compileStyleFiles($rootDir, $skin, $menuPos) {
	$cacheDir = "$rootDir/app/cache/less";
	if (!file_exists($cacheDir)) {
		mkdir($cacheDir, 0777, true);
	}
	Less_Cache::$cache_dir = $cacheDir;
	$lessFiles = array(
		"$skin.less" => '',
		"menu-$menuPos.less" => '',
	);
	$parserOptions = array(/*'compress' => true*/);
	$cssFile = Less_Cache::Get($lessFiles);
	return $cssFile ? $cacheDir .'/'. $cssFile : null;
}

function sendCssFile($cssFile) {
	header('Content-Type: text/css');
	header('Expires: Sun, 17-Jan-2038 19:14:07 GMT');
	header('Cache-Control: max-age=31536000'); // 1 year
	header('Last-Modified: Sun, 01 Jan 2001 00:00:01 GMT');
	readfile($cssFile);
}

$skins = array(
	'orange',
	'blackwhite',
	'blue',
	'neg',
	'olive',
	'phoenix',
	'pink',
	'purple',
);
$positions = array(
	'right',
	'left',
);

$rootDir = strpos(__DIR__, '/web/bundles/') === false
	? __DIR__.'/../../../../../..'
	: __DIR__.'/../../../..';
require __DIR__."/../bin/Less.php";
$cssFile = compileStyleFiles($rootDir, get('skin', $skins), get('menu', $positions));
if ($cssFile) {
	sendCssFile($cssFile);
} else {
	error_log(Less_Cache::$error->getMessage());
}
