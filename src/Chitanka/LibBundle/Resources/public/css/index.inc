<?php
/**
	Tiny CSS generator library
*/

function sendStyleFile($combiFile) {
	header('Content-Type: text/css');
	header('Expires: Sun, 17-Jan-2038 19:14:07 GMT');
	header('Cache-Control: max-age=315360000'); // 10 years
	header('Last-Modified: Sun, 01 Jan 2001 00:00:01 GMT');
	
	readfile($combiFile);
}


function getCombiFile($source)
{
	$out = '';
	$_GET['o'] = str_replace('.css', '', $source);

	ob_start();
	require dirname(__FILE__) . "/main.css";
	
	$parts = array_slice(explode(',', $_GET['o']), 2);
	foreach ($parts as $part) {
		$cssfile = dirname(__FILE__) . "/$part.css";
		if (file_exists($cssfile)) {
			include $cssfile;
		}
	}

	$out .= ob_get_clean() . "\n";

	return $out;
}


function createCombiFile($source, $combiName)
{
	$content = getCombiFile($source);
	$dir = dirname($combiName);
	if ( ! file_exists($dir)) {
		mkdir($dir, 0777, true);
	}
	//echo minifyStyle($content);
	file_put_contents($combiName, minifyStyle($content));
}

/*
	minify, but preserve some readability - every definition on a single line
*/
function minifyStyle($content)
{
	// remove comments
	$content = preg_replace('|/\*.+\*/|Ums', '', $content);
	// remove white-spaces before and after the rule name
	$content = preg_replace('/\s([\w-]+:) /', '$1', $content);
	$content = strtr($content, array(
		"\n" => '',
		' {' => '{',
		' }' => '}',
	));
	$content = strtr($content, array('}' => "}\n"));
	// remove the semicolon after the last rule
	$content = preg_replace('/; *}/', '}', $content);
	return $content;
}

function sanitizeInput($input) {
	$input = preg_replace('#[^a-zA-Z\d,._/-]#', '', $input);
	$input = strtr($input, array('..' => '.'));
	return $input;
}
