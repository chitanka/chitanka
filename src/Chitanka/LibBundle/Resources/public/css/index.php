<?php
/*
	This script receives base names of css files
	and serves them as a combined one.

	The file names should not have an extension,
	".css" is automatically appended.

	An example request would look like "./?screen=foo,bar;print=fee,bee;v=666".
	This should generate a file like this:
		@media screen {
			<contents of the files for screen styles>
		}
		@media print {
			<contents of the files for print styles>
		}
*/

require dirname(__FILE__) . '/index.inc';

if (strpos(realpath(dirname(__FILE__)), 'LibBundle') === false) {
	$cacheDir = dirname(__FILE__) . '/../cache'; // non-Symfony
} else {
	$cacheDir = __DIR__ . '/../../../../../../web/cache';
}

$query = $_SERVER['QUERY_STRING'];
// remove bad symbols from query
$query = preg_replace( '![^\w\d,.:;=-]!', '', $query );
$combiFile =  "$cacheDir/css/$query";

if ( ! file_exists($combiFile) ) {
	createCombiFile($query, $combiFile);
}

sendStyleFile($combiFile);
