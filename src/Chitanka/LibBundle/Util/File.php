<?php
namespace Chitanka\LibBundle\Util;

use Chitanka\LibBundle\Legacy\Legacy;

class File
{
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
		chmod($filename, 0644);

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


	public static function guessMimeType($file)
	{
		switch ( strtolower(self::getFileExtension($file)) ) {
			case 'png' : return 'image/png';
			case 'gif' : return 'image/gif';
			case 'jpg' :
			case 'jpeg': return 'image/jpeg';
		}

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		return $finfo->file($href);
	}


	public static function isArchive($file) {
		$exts = array('zip', 'tgz', 'tar.gz', 'bz2', 'tar.bz2');
		foreach ($exts as $ext) {
			if ( strpos($file, '.'.$ext) !== false ) {
				return true;
			}
		}
		return false;
	}

	public static function getFileExtension($filename)
	{
		return ltrim(strrchr($filename, '.'), '.');
	}


	public static function cleanFileName($fname, $woDiac = true) {
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
}
