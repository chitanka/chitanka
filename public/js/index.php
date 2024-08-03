<?php
/*
 * This tiny script receives base names of javascript files and serves them as a combined one.
 * Optionally, every file name can have a directory prefix with "--" as a directory separator.
 * For example: vendor--js--jquery
 *
 * The file names should be deleimited by a comma and should not have an extension,
 * ".js" is automatically appended.
 */

$query = sanitizeInput($_SERVER['QUERY_STRING']);
$webdir = __DIR__.'/..';
$path = "$webdir/cache/js";
$combiFile = "$path/$query";

if (!file_exists($combiFile)) {
	createCombiFile($query, $combiFile);
}

header('Content-Type: application/x-javascript; charset=UTF-8');
header('Expires: '.gmdate('r', strtotime('+1 year')));
header('Cache-Control: max-age=31536000'); // 1 year
header('Last-Modified: Sun, 01 Jan 2001 00:00:01 GMT');
readfile($combiFile);

function createCombiFile($query, $combiName) {
	global $webdir;

	$fileExt = '.js';
	$query = strtr($query, [$fileExt => '']);

	$out = '';
	foreach (explode(',', $query) as $name) {
		$name = str_replace('--', '/', $name); // directory separator
		$dirPrefix = strpos($name, '/') !== false ? "$webdir/" : '';
		$maxFile = $dirPrefix . $name . $fileExt;
		$minFile = $dirPrefix . $name . ".min$fileExt";
		$file = '';
		$isDebug = isset($_REQUEST['debug']) && $_REQUEST['debug'];
		if ( ! $isDebug && file_exists($minFile) ) {
			$file = $minFile;
		} else if ( file_exists( $maxFile ) ) {
			$file = $maxFile;
		}
		if ( ! empty($file) ) {
			$out .= "/*=$name*/\n" . file_get_contents($file) . "\n";
		}
	}
	$dir = dirname($combiName);
	if ( ! file_exists($dir)) {
		mkdir($dir, 0777, true);
	}
	file_put_contents($combiName, $out);
}

function sanitizeInput($input) {
	$input = preg_replace('#[^/a-zA-Z\d,._-]#', '', $input);
	$input = strtr($input, ['..' => '.']);
	return $input;
}
