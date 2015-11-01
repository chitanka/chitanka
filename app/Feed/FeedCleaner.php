<?php namespace App\Feed;

class FeedCleaner {

	/**
	 *
	 * @param string $content
	 * @return string
	 */
	public static function removeScriptContent($content) {
		$dirtyContents = $content;
		while (true) {
			$cleanedContents = preg_replace('|<\s*script[^>]*>.*<\s*/\s*script\s*>|Ums', '', $dirtyContents);
			if ($cleanedContents === $dirtyContents) {
				return $cleanedContents;
			}
			$dirtyContents = $cleanedContents;
		}
	}

	/**
	 *
	 * @param string $content
	 * @return string
	 */
	public static function removeImageBeacons($content) {
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
		}, $content);
	}

	public static function removeSocialFooterLinks($content) {
		return preg_replace('#<img alt="" border="0" src=".+feeds\.wordpress\.com/.+">#U', '', $content);
	}

	/**
	 *
	 * @param string $content
	 * @return string
	 */
	public static function removeExtraClosingTags($content) {
		return strtr($content, [
			'</img>' => '',
			'</br>' => '',
		]);
	}

	/**
	 * @param string $content
	 * @return string
	 */
	public static function cleanupPhpbbContent($content) {
		$cleanContent = strtr($content, [
			'&u=' => '&amp;u=', // user link
			'</span>' => '',
			"<br />\n<li>" => '</li><li>',
			"<br />\n</ul>" => '</li></ul>',
			' target="_blank"' => '',
		]);
		$cleanContent = preg_replace('|<span[^>]+>|', '', $cleanContent);
		return $cleanContent;
	}

	public function relativizeUrlProtocol($content) {
		$content = str_replace('http://', '//', $content);
		return $content;
	}

}
