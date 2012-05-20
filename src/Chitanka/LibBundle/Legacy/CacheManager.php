<?php
namespace Chitanka\LibBundle\Legacy;

use Chitanka\LibBundle\Util\File;

class CacheManager
{
	const ONEDAYSECS = 86400; // 60*60*24

	static private
		$cacheDir  = 'cache/',
		$dlDir     = 'cache/dl/',
		$zipDir    = 'zip/',

		/** Time to Live for download cache (in hours) */
		$dlTtl = 168;


	/**
		Tells whether a given cache file exists.
		If file age is given, older than that files are discarded.
		@param $action  Page action
		@param $id      File ID
		@param $age     File age in days
	*/
	static public function cacheExists($action, $id, $age = null)
	{
		$file = self::getPath($action, $id);
		if ( ! file_exists($file) ) {
			return false;
		}
		if ( is_null($age) ) {
			return true;
		}
		return (time() - filemtime($file)) < $age * self::ONEDAYSECS;
	}

	static public function getCache($action, $id, $compressed = false) {
		$c = file_get_contents( self::getPath($action, $id) );
		return $compressed ? gzinflate($c) : $c;
	}

	static public function setCache($action, $id, $content, $compressed = false) {
		$file = self::getPath($action, $id);
		File::myfile_put_contents($file, $compressed ? gzdeflate($content) : $content);
		return $content;
	}

	static public function clearCache($action, $id) {
		$file = self::getPath($action, $id);
		return file_exists($file) ? unlink($file) : true;
	}


	static public function dlCacheExists($id, $ext = '') {
		return file_exists( self::getDlCachePath($id, $ext) );
	}

	static public function getDlCache($id, $ext = '') {
		$file = self::getDlCachePath($id, $ext);
		touch($file); // mark it as fresh
		return file_get_contents($file);
	}

	static public function setDlCache($id, $content, $ext = '') {
		return File::myfile_put_contents(self::getDlCachePath($id, $ext), $content);
	}

	static public function clearDlCache($id) {
		$file = self::getDlCachePath($id);
		@unlink($file . '.fbi');
		@unlink($file . '.fb2');
		@unlink($file . '.txt');
		self::clearDl($id);
		self::clearMirrorCache($id);

		return file_exists($file) ? unlink($file) : true;
	}


	static public function clearMirrorCache($id)
	{
		foreach (Setup::setting('mirror_sites') as $mirror) {
			$url = sprintf('%s/clearCache?texts=%d', $mirror, $id);
			Legacy::getFromUrl($url);
		}
	}


	static public function getDlFile($fname) {
		$file = self::$dlDir . $fname;
		// commented, file can be non-existant
		#touch($file); // mark it as fresh
		return $file;
	}

	static public function setDlFile($fname, $fcontent) {
		return File::myfile_put_contents(self::$dlDir . $fname, $fcontent);
	}


	static public function dlFileExists($fname) {
		return file_exists(self::$dlDir . $fname);
	}

	/**
		Deletes all download files older than the time to live.
	*/
	static public function deleteOldDlFiles()
	{
		// disable until synced with the database
		return;

		$thresholdTime = time() - self::$dlTtl * 3600;
		$dh = opendir(self::$dlDir);
		if (!$dh) return;
		while (($file = readdir($dh)) !== false) {
			if ( $file[0] == '.' ) { continue; }
			$fullname = self::$dlDir . $file;
			if (filemtime($fullname) < $thresholdTime) {
				unlink($fullname);
			}
		}
		closedir($dh);
	}

	static public function getPath($action, $id) {
		$subdir = $action . '/';
		settype($id, 'string');
		$subsubdir = $id[0] . '/' . $id[1] . '/' . $id[2] . '/';
		return self::$cacheDir . $subdir . $subsubdir . $id;
	}

	static public function getDlCachePath($id, $ext = '') {
		return self::$cacheDir . self::$zipDir . Legacy::makeContentFilePath($id) . $ext;
	}



	static public function getDl($textIds, $format = '')
	{
		return self::getDlFileByHash( self::getHashForTextIds($textIds, $format) );
	}

	static public function setDl($textIds, $file, $format = '')
	{
		$db = Setup::db();
		$pk = self::getHashForTextIds($textIds, $format);
		$db->insert(DBT_DL_CACHE, array(
			"id = $pk",
			'file' => $file,
		), true, false);
		foreach ( (array) $textIds as $textId ) {
			$db->insert(DBT_DL_CACHE_TEXT, array(
				"dc_id = $pk",
				'text_id' => $textId,
			), true, false);
		}
		return $file;
	}

	static public function clearDl($textIds)
	{
		$db = Setup::db();
		$dctKey = array(
			'text_id' => is_array($textIds) ? array('IN', $textIds) : $textIds
		);
		$hashes = $db->getFieldsMulti(DBT_DL_CACHE_TEXT, $dctKey, 'dc_id');
		if ( ! empty($hashes) ) {
			self::clearDlFiles($hashes);
			$db->delete(DBT_DL_CACHE, array('id' => array('IN', $hashes)));
			$db->delete(DBT_DL_CACHE_TEXT, array('dc_id' => array('IN', $hashes)));
		}
	}


	static protected function clearDlFiles($hashes)
	{
		$files = Setup::db()->getFieldsMulti(DBT_DL_CACHE, array('id' => array('IN', $hashes)), 'file');
		foreach ($files as $file) {
			self::clearDlFile($file);
		}
	}

	static protected function clearDlFile($file)
	{
		$file = self::getDlFile($file);
		if ( file_exists($file) ) {
			return unlink($file);
		}
		return true;
	}


	static public function getDlFileByHash($hash)
	{
		return Setup::db()->getFields(DBT_DL_CACHE, array("id = $hash"), 'file');
	}

	static protected function getHashForTextIds($textIds, $format = '')
	{
		if ( is_array($textIds) ) {
			$textIds = implode(',', $textIds);
		}
		return '0x' . substr(md5($textIds . $format), 0, 16);
	}
}
