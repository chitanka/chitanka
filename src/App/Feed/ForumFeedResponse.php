<?php namespace App\Feed;

class ForumFeedResponse extends FeedResponse {

	/**
	 * @param string $content
	 * @return string
	 */
	protected function cleanupContent($content) {
		$cleanContent = parent::cleanupContent($content);
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

}
