<?php

namespace Chitanka\LibBundle\Controller;

use Chitanka\LibBundle\Legacy\CacheManager;
use Chitanka\LibBundle\Legacy\Legacy;

class WikiController extends Controller
{

	public function indexAction($page)
	{
		$url = str_replace('$1', ucfirst($page), $this->container->getParameter('wiki_url'));
		$this->view = array(
			'page' => $page,
			'wiki_page' => $url,
			'contents' => $this->getFromWiki($url)
		);

		return $this->display('index');
	}


	private function getFromWiki($url, $ttl = 1)
	{
		$id = md5($url);
		$action = 'wiki';

		if ($this->get('request')->query->get('cache', 1) == 0) {
			$ttl = 0;
		}

		if ( CacheManager::cacheExists($action, $id, $ttl) ) {
			return CacheManager::getCache($action, $id);
		}

		$content = Legacy::getFromUrl("$url?action=render");
		if ( empty($content) ) {
			return '';
		}

		return CacheManager::setCache($action, $id, $content);
	}

}
