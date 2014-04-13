<?php namespace Chitanka\LibBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Chitanka\LibBundle\Legacy\CacheManager;
use Chitanka\LibBundle\Legacy\Legacy;
use Chitanka\LibBundle\Service\WikiEngine;

class WikiController extends Controller {

	public function indexAction($page) {
		$url = str_replace('$1', ucfirst($page), $this->container->getParameter('wiki_url'));
		$this->view = array(
			'page' => $page,
			'wiki_page' => $url,
			'contents' => $this->getFromWiki($url)
		);

		return $this->display('index');
	}

	public function showAction($page) {
		$wiki = $this->wikiEngine();
		$wikiPage = $wiki->getPage($page);
		if (!$wikiPage->exists()) {
			$this->responseStatusCode = 404;
		}
		return $this->display('show', array(
			'page' => $wikiPage,
		));
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
		return $this->display('history', array(
			'page' => $wiki->getPage($page),
			'commits' => $commits,
		));
	}

	protected function wikiEngine() {
		return new WikiEngine($this->getParameter('content_dir').'/wiki');
	}

	private function getFromWiki($url, $ttl = 1) {
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
