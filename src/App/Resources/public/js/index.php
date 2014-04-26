<?php
/*
	This tiny script receives base names of javascript files
	and serves them as a combined one.

	It expects a comma as a delimiter of the file names.
	The file names should not have an extension,
	".js" is automatically appended.
*/

$query = sanitizeInput($_SERVER['QUERY_STRING']);
$curdir = dirname(__FILE__);
$path = strpos($curdir, '/bundles/') === false
	? "$curdir/../../../../../../web/cache"
	: "$curdir/../../../cache";
$combiFile = $path . sanitizeInput($_SERVER['REQUEST_URI']);

if ( ! file_exists($combiFile) ) {
	createCombiFile($query, $combiFile);
}

header('Content-Type: application/x-javascript; charset=UTF-8');
header('Expires: Sun, 17-Jan-2038 19:14:07 GMT');
header('Cache-Control: max-age=315360000'); // 10 years
header('Last-Modified: Sun, 01 Jan 2001 00:00:01 GMT');

readfile($combiFile);

function createCombiFile($query, $combiName)
{
	$fileExt = '.js';
	$query = strtr($query, array($fileExt => ''));

	$out = '';
	foreach ( explode(',', $query) as $name ) {
		$maxFile = "$name$fileExt";
		$minFile = "$name.min$fileExt";
		$file = '';
		$isDebug = isset($_REQUEST['debug']) && $_REQUEST['debug'];
		if ( ! $isDebug && file_exists($minFile) ) {
			$file = $minFile;
		} else if ( file_exists( $maxFile ) ) {
			$file = $maxFile;
		}
		if ( ! empty($file) ) {
			$out .= "/*=$file*/\n" . file_get_contents($file) . "\n";
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
	$input = strtr($input, array('..' => '.'));
	return $input;
}
