<?php namespace App\Service;

use App\Legacy\CacheManager;
use Buzz\Browser;

class MediawikiClient {

	private $browser;
	private $userAgentString = 'Mylib (http://chitanka.info)';

	public function __construct(Browser $browser) {
		$this->browser = $browser;
	}

	/**
	 * @param string $url
	 * @param int $cacheDays
	 * @return string
	 */
	public function fetchContent($url, $cacheDays = 7) {
		$id = md5($url);
		$action = 'info';

		if ( CacheManager::cacheExists($action, $id, $cacheDays) ) {
			return CacheManager::getCache($action, $id);
		}

		try {
			/* @var $response \Buzz\Message\Response */
			$response = $this->browser->get("{$url}?action=render", ["User-Agent: {$this->userAgentString}"]);
			if ($response->isOk()) {
				$content = $this->processContent($response->getContent(), $url);
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
