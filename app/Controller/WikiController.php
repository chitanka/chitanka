<?php namespace App\Controller;

use App\Legacy\CacheManager;
use App\Legacy\Legacy;
use App\Service\WikiEngine;
use Symfony\Component\HttpFoundation\Request;

class WikiController extends Controller {

	public function indexAction(Request $request, $page) {
		$url = str_replace('$1', ucfirst($page), $this->container->getParameter('wiki_url'));
		return array(
			'page' => $page,
			'wiki_page' => $url,
			'contents' => $this->getFromWiki($url, $request->query->get('cache', 1)),
		);
	}

	public function showAction($page) {
		$wiki = $this->wikiEngine();
		$wikiPage = $wiki->getPage($page);
		return array(
			'page' => $wikiPage,
			'_status' => !$wikiPage->exists() ? 404 : null,
		);
	}

	public function saveAction(Request $request) {
		$input = $request->request;
		$wiki = $this->wikiEngine();
		$user = $this->getUser();
		$wiki->savePage($input->get('summary'), $input->get('page'), $input->get('content'), $input->get('title'), "{$user->getUsername()} <{$user->getUsername()}@chitanka>");
		return $this->displayJson(1);
	}

	public function previewAction(Request $request) {
		return $this->displayText(WikiEngine::markdownToHtml($request->request->get('content')));
	}

	public function historyAction($page) {
		$wiki = $this->wikiEngine();
		$commits = $wiki->getHistory($page);
		return array(
			'page' => $wiki->getPage($page),
			'commits' => $commits,
		);
	}

	private function wikiEngine() {
		return new WikiEngine($this->container->getParameter('content_dir').'/wiki');
	}

	private function getFromWiki($url, $ttl = 1) {
		$id = md5($url);
		$action = 'wiki';

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
