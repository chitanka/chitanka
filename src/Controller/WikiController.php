<?php namespace App\Controller;

use App\Service\WikiEngine;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

class WikiController extends Controller {

	public function showAction($page) {
		if (strpos($page, 'content/') === 0) {
			return $this->renderBinaryFile(str_replace('content/', '', $page));
		}
		$wiki = $this->wikiEngine();
		$wikiPage = $wiki->getPage($page);
		return [
			'page' => $wikiPage,
			'_status' => !$wikiPage->exists() ? 404 : null,
		];
	}

	public function saveAction(Request $request) {
		$input = $request->request;
		$wiki = $this->wikiEngine();
		$user = $this->getUser();
		$wiki->savePage($input->get('summary'), $input->get('page'), $input->get('content'), $input->get('title'), "{$user->getUsername()} <{$user->getUsername()}@chitanka>");
		return $this->asJson(1);
	}

	public function previewAction(Request $request) {
		return $this->asText(WikiEngine::markdownToHtml($request->request->get('content')), 'text/html');
	}

	public function historyAction($page) {
		$wiki = $this->wikiEngine();
		$commits = $wiki->getHistory($page);
		return [
			'page' => $wiki->getPage($page),
			'commits' => $commits,
		];
	}

	private function wikiEngine() {
		return new WikiEngine($this->container->getParameter('content_dir').'/wiki');
	}

	private function renderBinaryFile($path) {
		return new BinaryFileResponse($this->container->getParameter('content_dir').'/'.$path);
	}
}
