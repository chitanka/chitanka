<?php namespace App\Service;

use App\Entity\WikiSiteRepository;
use App\Service\MediawikiClient;

class WikiReader {

	private $mwClient;
	private $siteRepo;

	public function __construct(MediawikiClient $mwClient, WikiSiteRepository $siteRepo) {
		$this->mwClient = $mwClient;
		$this->siteRepo = $siteRepo;
	}

	public function fetchPage($fullPageName) {
		list($wikiCode, $pageName) = explode(':', $fullPageName, 2);
		$site = $this->siteRepo->findSiteByCode($wikiCode);
		$url = $site->getUrl($pageName);
		$page = new WikiPage($pageName);
		$page->content = $this->mwClient->fetchContent($url);
		$page->intro = strtr($site->getIntro(), [
			'$1' => $pageName,
			'$2' => $url,
		]);
		return $page;
	}
}

class WikiPage {

	public $name;
	public $intro;
	public $content;

	public function __construct($name) {
		$this->name = $name;
	}
}
