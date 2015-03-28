<?php namespace App\Util;

class HttpAgent {
	public function urlExists($url) {
		$c = curl_init();
		curl_setopt_array($c, [
			CURLOPT_URL             => $url,
			CURLOPT_RETURNTRANSFER  => true,
			CURLOPT_CUSTOMREQUEST   => 'HEAD',
			CURLOPT_HEADER          => true,
			// Wikimedia requires a proper user-agent string
			// http://mediawiki.org/wiki/API:Quick_start_guide#Identifying_your_client
			CURLOPT_USERAGENT       => 'User-Agent: PHP (chitanka.info link checker, http://chitanka.info/feedback)',
		]);
		$res = curl_exec($c);
		curl_close($c);

		if (preg_match('/ERR_ACCESS_DENIED/', $res)) {
			// an error page is served
			// could not determine if the URL exists or not
			return true;
		}
		$exists = preg_match('/^HTTP.+ 200 OK/', $res);

		return $exists;
	}
}
