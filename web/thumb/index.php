<?php
function genThumbnail($filename, $thumbname, $width = 250, $quality = 90) {
	$dir = dirname($thumbname);
	if ( ! file_exists($dir)) {
		mkdir($dir, 0755, true);
	}

	if (!function_exists('imagecreatetruecolor')) {
		symlink($filename, $thumbname);
		return $thumbname;
	}

	list($width_orig, $height_orig) = getimagesize($filename);
	if ($width == 'max' || $width_orig < $width) {
		symlink($filename, $thumbname);
		return $thumbname;
	}

	$height = $width * $height_orig / $width_orig;

	$image_p = imagecreatetruecolor($width, $height);

	$extension = ltrim(strrchr($filename, '.'), '.');
	switch ($extension) {
		case 'jpg':
		case 'jpeg':
			$image = imagecreatefromjpeg($filename);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
			imagejpeg($image_p, $thumbname, $quality);
			break;
		case 'png':
			$image = imagecreatefrompng($filename);
			imagealphablending($image_p, false);
			$color = imagecolortransparent($image_p, imagecolorallocatealpha($image_p, 0, 0, 0, 127));
			imagefill($image_p, 0, 0, $color);
			imagesavealpha($image_p, true);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
			imagepng($image_p, $thumbname, 9);
			break;
	}

	return $thumbname;
}

function serveFile($file, $format) {
	$expires = 30240000; // 350 days
	header("Cache-Control: maxage=$expires");
	header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
	header('Content-Type: image/'.$format);
	readfile($file);
	exit;
}

$query = ltrim($_SERVER['QUERY_STRING'], '/');
$query = strtr($query, array('..' => '.'));
list($name, $width, $format) = explode('.', basename($query));
$contentPath = __DIR__.'/../content/';
if (!file_exists($contentPath)) {
	$contentPath = __DIR__.'/../../../chitanka-content/';
}
$file = $contentPath . dirname($query) . "/$name.$format";
if ($format == 'jpg') {
	$format = 'jpeg';
}
$thumb = __DIR__ . "/../cache/thumb/$query";
$thumbReal = realpath($thumb);
if ($thumbReal) {
	serveFile($thumbReal, $format);
}
if (file_exists($file)) {
	serveFile(genThumbnail($file, $thumb, $width, 90), $format);
}
header('HTTP/1.1 404 Not Found');
error_log($file.' not found');
