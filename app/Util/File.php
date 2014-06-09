<?php namespace App\Util;

use App\Legacy\Legacy;

class File {

	static public function mycopy($source, $dest) {
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

	static public function myfile_put_contents($filename, $data, $flags = null) {
		if (is_dir($filename)) {
			return false;
		}
		self::make_parent($filename);
		$res = file_put_contents($filename, $data, $flags);

		return $res;
	}

	static public function mymove_uploaded_file($tmp, $dest) {
		self::make_parent($dest);
		return move_uploaded_file($tmp, $dest);
	}

	static public function make_parent( $filename ) {
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
	static public function guessMimeType($file) {
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
	static public function isArchive($file) {
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
	static public function getFileExtension($filename) {
		return ltrim(strrchr($filename, '.'), '.');
	}

	/**
	 * @param string $fname
	 * @param bool $woDiac
	 */
	static public function cleanFileName($fname, $woDiac = true) {
		$fname = preg_replace('![^a-zA-Z0-9_. -]!u', '', $fname);
		if ( $woDiac ) {
			$fname = Legacy::removeDiacritics($fname);
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
	static public function isSFB($file) {
		if ( (strpos($file, '.sfb') !== false) && file_exists($file) ) {
			$cont = file_get_contents( $file,  false, NULL, -1, 10 );
			if ( strpos($cont, chr(124).chr(9)) !== false )
				return true;
		}

		return false;
	}

	static public function hasValidExtension($filename, $validExtensions) {
		foreach ($validExtensions as $validExtension) {
			if (preg_match("/\.$validExtension$/i", $filename)) {
				return true;
			}
		}
		return false;
	}
}
