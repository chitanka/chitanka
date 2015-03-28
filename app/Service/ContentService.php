<?php namespace App\Service;

use App\Util\Ary;

class ContentService {

	private static $contentDirs = [
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
	];

	/**
	 * @param string $key
	 * @param int $num
	 * @return string
	 */
	public static function getContentFile($key, $num) {
		$file = self::getInternalContentFilePath($key, $num);
		if (file_exists($file)) {
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
}
