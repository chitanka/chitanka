<?php namespace App\Legacy;

use App\Util\File;
use App\Service\ContentService;

class CacheManager {

	const ONEDAYSECS = 86400; // 60*60*24

	private static
		$cacheDir  = 'cache/',
		$dlDir     = 'cache/dl/',
		$zipDir    = 'zip/';

	/**
	 * Tells whether a given cache file exists.
	 * If file age is given, older than that files are discarded.
	 * @param string $action  Page action
	 * @param string $id      File ID
	 * @param int $age     File age in days
	 */
	public static function cacheExists($action, $id, $age = null) {
		$file = self::getPath($action, $id);
		if ( ! file_exists($file) ) {
			return false;
		}
		if ( is_null($age) ) {
			return true;
		}
		return (time() - filemtime($file)) < $age * self::ONEDAYSECS;
	}

	/**
	 * @param string $action
	 * @param string $id
	 * @param bool $compressed
	 */
	public static function getCache($action, $id, $compressed = false) {
		$c = file_get_contents( self::getPath($action, $id) );
		return $compressed ? gzinflate($c) : $c;
	}

	/**
	 * @param string $action
	 * @param string $id
	 * @param string $content
	 * @param bool $compressed
	 */
	public static function setCache($action, $id, $content, $compressed = false) {
		$file = self::getPath($action, $id);
		File::myfile_put_contents($file, $compressed ? gzdeflate($content) : $content);
		return $content;
	}

	/**
	 * @param string $id
	 * @param string $ext
	 * @return bool
	 */
	public static function dlCacheExists($id, $ext = '') {
		return file_exists( self::getDlCachePath($id, $ext) );
	}

	/**
	 * @param string $id
	 * @param string $ext
	 * @return string
	 */
	public static function getDlCache($id, $ext = '') {
		$file = self::getDlCachePath($id, $ext);
		touch($file); // mark it as used
		return file_get_contents($file);
	}

	/**
	 * @param string $id
	 * @param string $content
	 * @param string $ext
	 */
	public static function setDlCache($id, $content, $ext = '') {
		return File::myfile_put_contents(self::getDlCachePath($id, $ext), $content);
	}

	/**
	 * @param string $fname
	 * @return string
	 */
	public static function getDlFile($fname) {
		return self::$dlDir . $fname;
	}

	/**
	 * @param string $fname
	 * @param string $fcontent
	 */
	public static function setDlFile($fname, $fcontent) {
		return File::myfile_put_contents(self::$dlDir . $fname, $fcontent);
	}

	/**
	 * @param string $action
	 * @param string $id
	 * @return string
	 */
	private static function getPath($action, $id) {
		$subdir = $action . '/';
		settype($id, 'string');
		$subsubdir = $id[0] . '/' . $id[1] . '/' . $id[2] . '/';
		return self::$cacheDir . $subdir . $subsubdir . $id;
	}

	/**
	 * @param string $id
	 * @param string $ext
	 * @return string
	 */
	private static function getDlCachePath($id, $ext = '') {
		return self::$cacheDir . self::$zipDir . ContentService::makeContentFilePath($id) . $ext;
	}

	/**
	 * @param array $textIds
	 * @param string $format
	 * @return string
	 */
	public static function getDl($textIds, $format = '') {
		return self::getDlFileByHash( self::getHashForTextIds($textIds, $format) );
	}

	/**
	 * @param array $textIds
	 * @param string $file
	 * @param string $format
	 */
	public static function setDl($textIds, $file, $format = '') {
		$db = Setup::db();
		$pk = self::getHashForTextIds($textIds, $format);
		$db->insert(DBT_DL_CACHE, [
			"id = $pk",
			'file' => $file,
		], true, false);
		foreach ( (array) $textIds as $textId ) {
			$db->insert(DBT_DL_CACHE_TEXT, [
				"dc_id = $pk",
				'text_id' => $textId,
			], true, false);
		}
		return $file;
	}

	/**
	 * @param array|int $textIds
	 */
	public static function clearDl($textIds) {
		$db = Setup::db();
		$dctKey = [
			'text_id' => is_array($textIds) ? ['IN', $textIds] : $textIds
		];
		$hashes = $db->getFieldsMulti(DBT_DL_CACHE_TEXT, $dctKey, 'dc_id');
		if ( ! empty($hashes) ) {
			$db->delete(DBT_DL_CACHE, ['id' => ['IN', $hashes]]);
			$db->delete(DBT_DL_CACHE_TEXT, ['dc_id' => ['IN', $hashes]]);
		}
	}

	/**
	 * @param string $hash
	 */
	public static function getDlFileByHash($hash) {
		return Setup::db()->getFields(DBT_DL_CACHE, ["id = $hash"], 'file');
	}

	/**
	 * @param array $textIds
	 * @param string $format
	 * @return string
	 */
	private static function getHashForTextIds($textIds, $format = '') {
		if ( is_array($textIds) ) {
			$textIds = implode(',', $textIds);
		}
		return '0x' . substr(md5($textIds . $format), 0, 16);
	}
}
