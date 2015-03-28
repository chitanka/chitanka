<?php namespace App\Entity;

/**
 *
 */
class SiteRepository extends EntityRepository {
	/** @return Site */
	public function findOneByUrlOrCreate($url) {
		$site = $this->findOneBy(['url' => $url]);
		if (!$site) {
			$site = new Site;
			$site->setUrl($url);
		}

		return $site;
	}
}
