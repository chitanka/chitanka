<?php namespace App\Legacy;

use App\Util\Number;

class Legacy {

	public static function getMaxUploadSizeInMiB() {
		return Number::int_b2m(Number::iniBytes(ini_get('upload_max_filesize')));
	}

	/**
	 * @param string $url
	 * @param array $postData
	 * @return string
	 */
	public static function getFromUrl($url, array $postData = []) {
		$ch = curl_init();

		$options = [
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,    // return content
			CURLOPT_HEADER         => false,   // don't return headers
			CURLOPT_CONNECTTIMEOUT => 30,      // timeout on connect
			CURLOPT_TIMEOUT        => 60,      // timeout on response
			CURLOPT_USERAGENT      => 'Mylib (http://chitanka.info)',
			CURLOPT_FOLLOWLOCATION => true,
		];
		if ( ! empty($postData)) {
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = $postData;
		}

		curl_setopt_array($ch, $options);
		$contents = curl_exec($ch);

		curl_close($ch);

		return $contents;
	}

	/**
	 * @param string $url
	 * @param int $cacheTime
	 * @return string
	 */
	public static function getFromUrlOrCache($url, $cacheTime = 0) {
		$id = md5($url);
		$action = 'url';

		if ( $cacheTime && CacheManager::cacheExists($action, $id, $cacheTime) ) {
			return CacheManager::getCache($action, $id);
		}

		$content = self::getFromUrl($url);
		if ( empty($content) ) {
			return '';
		}

		return CacheManager::setCache($action, $id, $content);
	}

	/**
	 * @param string $pass
	 * @param int $loops
	 */
	public static function sha1_loop($pass, $loops = 1) {
		for ($i=0; $i < $loops; $i++) {
			$pass = sha1($pass);
		}

		return $pass;
	}

}
