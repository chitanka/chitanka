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
		if ($site === null) {
			return null;
		}
		$url = $site->getUrl($pageName);
		$page = new WikiReaderPage($pageName);
		$page->content = $this->mwClient->fetchContent($url);
		$page->intro = strtr($site->getIntro(), [
			'$1' => $pageName,
			'$2' => $url,
		]);
		return $page;
	}
}
