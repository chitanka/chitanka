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

function compileStyleFiles($cacheDir, $skin, $menuPos) {
	if (!file_exists($cacheDir)) {
		mkdir($cacheDir, 0777, true);
	}
	Less_Cache::$cache_dir = $cacheDir;
	$lessFiles = array(
		// \n needed for Less.php to recognize this as LESS code
		'menu' => "@menu-position: '$menuPos';\n",
		"$skin.less" => '',
	);
	$parserOptions = array(
		//'compress' => true,
	);
	$cssFile = Less_Cache::Get($lessFiles, $parserOptions);
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

$thisDir = dirname(__FILE__);
$cacheDir = strpos($thisDir, '/bundles/') === false
	? $thisDir.'/../../../../../../app/cache/less'
	: $thisDir.'/../../../cache/less';
require $thisDir."/../bin/Less.php";
ini_set('memory_limit', '128M');
$cssFile = compileStyleFiles($cacheDir, get('skin', $skins), get('menu', $positions));
if ($cssFile) {
	sendCssFile($cssFile);
} else {
	error_log(Less_Cache::$error->getMessage());
}
