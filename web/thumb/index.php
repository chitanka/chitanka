<?php
function genThumbnail($filename, $thumbname, $width = 250, $quality = 90)
{
	$dir = dirname($thumbname);
	if ( ! file_exists($dir)) {
		mkdir($dir, 0755, true);
	}

	list($width_orig, $height_orig) = getimagesize($filename);
	if ($width_orig < $width) {
		copy($filename, $thumbname);

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
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
			imagepng($image_p, $thumbname, 9);
			break;
	}

	return $thumbname;
}


$query = $_SERVER['QUERY_STRING'];

list($name, $width, $format) = explode('.', basename($query));
$file = sprintf('%s/../content/%s/%s.%s', dirname(__FILE__), dirname($query), $name, $format);

if ($format == 'jpg') {
	$format = 'jpeg';
}

if (file_exists($file)) {
	header('Content-Type: image/'.$format);
	$thumb = sprintf('%s/../cache/thumb/%s', dirname(__FILE__), $query);
	readfile(genThumbnail($file, $thumb, $width, 90));
} else {
	header('HTTP/1.1 404 Not Found');
	echo $file, ' no found';
}
