<?php namespace App\Feed;

use App\Legacy\Legacy;

class FeedFetcher {

	const DEFAULT_CACHE_DAYS = 0.02;

	/**
	 * @param string $xmlFile    An XML feed document
	 * @param string $xslFile    An XSLT document used for transformation into HTML
	 * @param float $cacheDays   Cache the fetched document for given days
	 * @return FeedResponse
	 */
	public function fetchAndTransform($xmlFile, $xslFile, $cacheDays = self::DEFAULT_CACHE_DAYS) {
		$proc = new \XSLTProcessor();
		$xsl = new \DOMDocument();

		if ($xsl->loadXML(file_get_contents($xslFile))) {
			$proc->importStyleSheet($xsl);
		}

		$feed = new \DOMDocument();
		$contents = Legacy::getFromUrlOrCache($xmlFile, $cacheDays);
		if (! empty($contents) && $feed->loadXML($contents) ) {
			return new FeedResponse($proc->transformToXML($feed));
		}

		return new FeedResponse();
	}
}
