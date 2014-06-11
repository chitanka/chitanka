<?php namespace App\Util;

class File {

	private static $contentDirs = array(
		'text' => 'content/text/',
		'text-info' => 'content/text-info/',
		'text-anno' => 'content/text-anno/',
		'user' => 'content/user/',
		'sandbox' => 'content/user/sand/',
		'info' => 'content/info/',
		'img' => 'content/img/',
		'cover' => 'content/cover/',
		'book' => 'content/book/',
		'book-anno' => 'content/book-anno/',
		'book-info' => 'content/book-info/',
		'book-img' => 'content/book-img/',
		'book-cover' => 'thumb/book-cover/',
		'book-cover-content' => 'content/book-cover/',
		'book-djvu' => 'content/book-djvu/',
		'book-pdf' => 'content/book-pdf/',
		'book-pic' => 'content/book-pic/',
	);

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

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		return $finfo->file($file);
	}

	/**
	 * @param string $file
	 */
	public static function isArchive($file) {
		$exts = array('zip', 'tgz', 'tar.gz', 'bz2', 'tar.bz2');
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
		$fname = strtr($fname, array(
			' .' => '', // from empty series number
			' '  => '_',
		));

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
	 * @param string $key
	 * @param int $num
	 * @return string
	 */
	public static function getContentFile($key, $num) {
		$file = self::getInternalContentFilePath($key, $num);
		if ( file_exists($file) ) {
			return file_get_contents($file);
		}

		return null;
	}

	/**
	 * @param string $key
	 * @param int $num
	 * @param bool $full
	 * @return string
	 */
	public static function getContentFilePath($key, $num, $full = true) {
		$pref = Ary::arrVal(self::$contentDirs, $key, $key .'/');

		return $pref . self::makeContentFilePath($num, $full);
	}

	/**
	 * @param string $key
	 * @param int $num
	 * @param bool $full
	 * @return string
	 */
	public static function getInternalContentFilePath($key, $num, $full = true) {
		return __DIR__ .'/../../web/'. self::getContentFilePath($key, $num, $full);
	}

	/**
	 * TODO use this for sfbzip too
	 * @param int $num
	 * @param bool $full
	 * @return string
	 */
	public static function makeContentFilePath($num, $full = true) {
		$realnum = $num;
		$num = (int) $num;
		$word = 4; // a word is four bytes long
		$bin_in_hex = 4; // one hex character corresponds to four binary digits
		$path = str_repeat('+/', $num >> ($word * $bin_in_hex));
		$hex = str_pad(dechex($num), $word, '0', STR_PAD_LEFT);
		$hex = substr($hex, -$word); // take last $word characters
		$path .= substr($hex, 0, 2) . '/';
		if ($full) {
			$path .= $realnum;
		}

		return $path;
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

		$temp = Setup::setting('tmp_dir').'/thumb-'.uniqid().'-'.basename($filename);
		imagejpeg($image_p, $temp, 80);

		return $temp;
	}
}
