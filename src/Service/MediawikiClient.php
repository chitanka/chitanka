<?php namespace App\Service;

use App\Legacy\CacheManager;

class MediawikiClient {

	private $userAgentString = 'Mylib (http://chitanka.info)';

	/**
	 * @param string $url
	 * @param int $cacheDays
	 * @return string
	 */
	public function fetchContent($url, $cacheDays = 1) {
		$id = md5($url);
		$action = 'info';

		if ( CacheManager::cacheExists($action, $id, $cacheDays) ) {
			return CacheManager::getCache($action, $id);
		}

		try {
			$response = file_get_contents("{$url}?action=render", false, stream_context_create([
				'http' => [
					'user_agent' => $this->userAgentString,
				],
			]));
			if ($response) {
				$content = $this->processContent($response, $url);
				return CacheManager::setCache($action, $id, $content);
			}
		} catch (\RuntimeException $e) {
			error_log($e->getMessage());
		}

		return null;
	}

	/**
	 * @param string $content
	 * @param string $url
	 * @return string
	 */
	private function processContent($content, $url) {
		$up = parse_url($url);
		$server = "$up[scheme]://$up[host]";
		$content = strtr($content, [
			'&nbsp;' => '&#160;',
			' href="/wiki/' => ' href="'.$server.'/wiki/',
		]);
		$patterns = [
			'/rel="[^"]+"/' => '',
			// images
			'| src="(/\w)|' => " src=\"$server$1",
		];
		$content = preg_replace(array_keys($patterns), array_values($patterns), $content);

		$content = sprintf('<div class="editsection">[<a href="%s?action=edit" title="Редактиране на статията">±</a>]</div>', $url) . $content;

		return $content;
	}
}
