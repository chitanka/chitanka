<?php namespace App\Controller;

use App\Service\WikiEngine;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

class WikiController extends Controller {

	public function showAction($page, string $contentDir) {
		if (strpos($page, 'content/') === 0) {
			return $this->renderBinaryFile($contentDir, str_replace('content/', '', $page));
		}
		$wiki = $this->wikiEngine($contentDir);
		$wikiPage = $wiki->getPage($page);
		return [
			'page' => $wikiPage,
			'_status' => !$wikiPage->exists() ? 404 : null,
		];
	}

	public function saveAction(Request $request, string $contentDir) {
		$input = $request->request;
		$wiki = $this->wikiEngine($contentDir);
		$user = $this->getUser();
		$wiki->savePage($input->get('summary'), $input->get('page'), $input->get('content'), $input->get('title'), "{$user->getUsername()} <{$user->getUsername()}@chitanka>");
		return $this->asJson(1);
	}

	public function previewAction(Request $request) {
		return $this->asText(WikiEngine::markdownToHtml($request->request->get('content')), 'text/html');
	}

	public function historyAction($page, string $contentDir) {
		$wiki = $this->wikiEngine($contentDir);
		$commits = $wiki->getHistory($page);
		return [
			'page' => $wiki->getPage($page),
			'commits' => $commits,
		];
	}

	private function wikiEngine(string $contentDir) {
		return new WikiEngine($contentDir.'/wiki');
	}

	private function renderBinaryFile(string $contentDir, $path) {
		return new BinaryFileResponse($contentDir.'/'.$path);
	}
}
