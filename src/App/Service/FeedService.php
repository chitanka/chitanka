<?php namespace App\Service;

use App\Legacy\Legacy;

class FeedService {

	const DEFAULT_CACHE_DAYS = 0.02;

	public function fetchAndTransform($xmlFile, $xslFile, $cacheDays = self::DEFAULT_CACHE_DAYS) {
		$proc = new \XSLTProcessor();
		$xsl = new \DOMDocument();

		if ($xsl->loadXML(file_get_contents($xslFile)) ) {
			$proc->importStyleSheet($xsl);
		}

		$feed = new \DOMDocument();
		$contents = Legacy::getFromUrlOrCache($xmlFile, $cacheDays);
		if (empty($contents)) {
			return false;
		}
		if ( $feed->loadXML($contents) ) {
			return $proc->transformToXML($feed);
		}

		return false;
	}

	public function limitArticles($content, $limit) {
		preg_match_all('|<article.+</article>|Ums', $content, $matches);
		$content = '';
		for ($i = 0; $i < $limit; $i++) {
			$content .= $matches[0][$i];
		}

		return $content;
	}

	public function cleanup($content) {
		$content = $this->removeScriptContent($content);
		$content = $this->removeImageBeacons($content);
		return $content;
	}

	public function cleanupForumFeed($content) {
		$cleanContent = strtr($content, array(
			'&u=' => '&amp;u=', // user link
			'</span>' => '',
			"<br />\n<li>" => '</li><li>',
			"<br />\n</ul>" => '</li></ul>',
			' target="_blank"' => '',
		));
		$cleanContent = preg_replace('|<span[^>]+>|', '', $cleanContent);
		return $cleanContent;
	}

	public function removeScriptContent($contents) {
		$dirtyContents = $contents;
		while (true) {
			$cleanedContents = preg_replace('|<\s*script[^>]*>.*<\s*/\s*script\s*>|Ums', '', $dirtyContents);
			if ($cleanedContents === $dirtyContents) {
				return $cleanedContents;
			}
			$dirtyContents = $cleanedContents;
		}
	}

	public function removeImageBeacons($contents) {
		$minWidthOrHeight = 4;
		return preg_replace_callback('|<\s*img [^>]+>|', function($match) use ($minWidthOrHeight) {
			foreach (explode(' ', $match[0]) as $attr) {
				if (strpos($attr, '=') === false) {
					continue;
				}
				list($name, $value) = explode('=', $attr);
				if ($name != 'width' && $name != 'height') {
					continue;
				}
				$intValue = trim($value, '\'"');
				if ($intValue < $minWidthOrHeight) {
					return '';
				}
			}
			return $match[0];
		}, $contents);
	}
}
