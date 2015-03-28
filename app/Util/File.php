<?php namespace App\Util;

class File {

	/**
	 * Byte order mark for some windows software
	 * @return string
	 */
	public static function getBom() {
		return "\xEF\xBB\xBF";
	}

	public static function mycopy($source, $dest) {
		if ( is_dir($source) ) {
			foreach ( scandir($source) as $file ) {
				if ( $file[0] == '.' ) continue;
				self::mycopy("$source/$file", "$dest/$file");
			}
			return true;
		}
		self::make_parent($dest);
		return copy($source, $dest);
	}

	public static function myfile_put_contents($filename, $data, $flags = null) {
		if (is_dir($filename)) {
			return false;
		}
		self::make_parent($filename);
		$res = file_put_contents($filename, $data, $flags);

		return $res;
	}

	public static function mymove_uploaded_file($tmp, $dest) {
		self::make_parent($dest);
		return move_uploaded_file($tmp, $dest);
	}

	public static function make_parent( $filename ) {
		$dir = dirname( $filename );
		if ( file_exists( $dir ) ) {
			@touch( $dir );
		} else {
			mkdir( $dir, 0755, true );
		}
	}

	/**
	 * @param string $file
	 */
	public static function guessMimeType($file) {
		switch ( strtolower(self::getFileExtension($file)) ) {
			case 'png' : return 'image/png';
			case 'gif' : return 'image/gif';
			case 'jpg' :
			case 'jpeg': return 'image/jpeg';
		}

		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		return $finfo->file($file);
	}

	/**
	 * @param string $file
	 */
	public static function isArchive($file) {
		$exts = ['zip', 'tgz', 'tar.gz', 'bz2', 'tar.bz2'];
		foreach ($exts as $ext) {
			if ( strpos($file, '.'.$ext) !== false ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $filename
	 */
	public static function getFileExtension($filename) {
		return ltrim(strrchr($filename, '.'), '.');
	}

	/**
	 * @param string $fname
	 * @param bool $woDiac
	 */
	public static function cleanFileName($fname, $woDiac = true) {
		$fname = preg_replace('![^a-zA-Z0-9_. -]!u', '', $fname);
		if ( $woDiac ) {
			$fname = String::removeDiacritics($fname);
		}
		$fname = preg_replace('/  +/', ' ', $fname);
		$fname = str_replace('- -', '-', $fname); // from empty entities
		$fname = trim($fname, '.- ');
		$fname = strtr($fname, [
			' .' => '', // from empty series number
			' '  => '_',
		]);

		return $fname;
	}

	/**
	 * @param string $file
	 */
	public static function isSFB($file) {
		if ( (strpos($file, '.sfb') !== false) && file_exists($file) ) {
			$cont = file_get_contents( $file,  false, NULL, -1, 10 );
			if ( strpos($cont, chr(124).chr(9)) !== false )
				return true;
		}

		return false;
	}

	public static function hasValidExtension($filename, $validExtensions) {
		foreach ($validExtensions as $validExtension) {
			if (preg_match("/\.$validExtension$/i", $filename)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Generate a thumbnail. Handles only JPEG.
	 * @param string $filename
	 * @param int $width
	 * @return string Thumbnail file name
	 */
	public static function genThumbnail($filename, $width = 250) {
		if ( ! preg_match('/\.jpe?g$/', $filename) ) {
			return $filename;
		}

		list($width_orig, $height_orig) = getimagesize($filename);
		if ($width_orig < $width) {
			return $filename;
		}

		$height = $width * $height_orig / $width_orig;

		$image_p = imagecreatetruecolor($width, $height);
		$image = imagecreatefromjpeg($filename);
		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

		$temp = sys_get_temp_dir().'/chitanka-thumb-'.uniqid().'-'.basename($filename);
		imagejpeg($image_p, $temp, 80);

		return $temp;
	}
}
