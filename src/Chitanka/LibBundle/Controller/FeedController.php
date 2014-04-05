<?php

namespace Chitanka\LibBundle\Controller;

use Chitanka\LibBundle\Service\FeedService;
use Chitanka\LibBundle\Legacy\Legacy;

class FeedController extends Controller
{
	protected $responseAge = 1800;


	public function lastLiternewsAction($limit = 3)
	{
		$feedUrl = 'http://planet.chitanka.info/atom.xml';
		$xsl = __DIR__.'/../Resources/transformers/forum-atom-compact.xsl';

		$content = $this->fetchFeed($feedUrl, $xsl);
		if ($content === false) {
			return $this->displayText('<p class="error">Неуспех при вземането на последните литературни новини.</p>');
		}

		$content = $this->limitArticles($content, $limit);
		$feedService = new FeedService();
		$content = $feedService->cleanup($content);

		return $this->displayText($content);
	}


	public function lastInsideNewsAction($limit = 8)
	{
		$feedUrl = 'http://identi.ca/api/statuses/user_timeline/127745.atom';
		$xsl = __DIR__.'/../Resources/transformers/forum-atom-compact.xsl';

		$content = $this->fetchFeed($feedUrl, $xsl);
		if ($content === false) {
			return $this->displayText('<p class="error">Неуспех при вземането на последните съобщения от identi.ca.</p>');
		}

		$content = $this->limitArticles($content, $limit);

		return $this->displayText($content);
	}


	public function lastForumPostsAction($limit = 5)
	{
		$feedUrl = 'http://forum.chitanka.info/feed.php?c=' . $limit;
		$xsl = __DIR__.'/../Resources/transformers/forum-atom-compact.xsl';

		$content = $this->fetchFeed($feedUrl, $xsl);
		if ($content === false) {
			return $this->displayText('<p class="error">Неуспех при вземането на последните форумни съобщения.</p>');
		}

		$content = strtr($content, array(
			'&u=' => '&amp;u=', // user link
			'</span>' => '',
			"<br />\n<li>" => '</li><li>',
			"<br />\n</ul>" => '</li></ul>',
			' target="_blank"' => '',
		));
		$content = preg_replace('|<span[^>]+>|', '', $content);

		return $this->displayText($content);
	}


	public function randomReviewAction()
	{
		$reviews = $this->getReviews(1, true);
		if (empty($reviews)) {
			return $this->displayText('No reviews found');
		}

		$this->view['book'] = $reviews[0];

		return $this->display('FeaturedBook:book');
	}


	public function reviewsAction()
	{
		$reviews = $this->getReviews();
		if (empty($reviews)) {
			return $this->displayText('No reviews found');
		}

		$this->view = compact('reviews');

		return $this->display('Review:index');
	}


	public function getReviews($limit = null, $random = false)
	{
		$reviews = array();
		$feedUrl = 'http://blog.chitanka.info/section/reviews/feed';
		$feed = Legacy::getFromUrlOrCache($feedUrl, $days = 0.02);
		if (empty($feed) || strpos($feed, '<atom') === false) {
			return $reviews;
		}

		$feedTree = new \SimpleXMLElement($feed);
		foreach ($feedTree->xpath('//item') as $item) {
			$content = $item->children('content', true)->encoded;
			if (preg_match('|<img src="(.+)" title="„(.+)“ от (.+)"|U', $content, $matches)) {
				$reviews[] = array(
					'id' => 0,
 					'author' => $matches[3],
					'title'  => $matches[2],
					'url'    => $item->link->__toString(),
					'cover'  => $matches[1],
					'description' => $item->description,
				);
			}
		}

		if ($random) {
			shuffle($reviews);
		}

		if ($limit) {
			$reviews = array_slice($reviews, 0, $limit);
		}

		return $reviews;
	}


	public function fetchFeed($xmlFile, $xslFile)
	{
		$proc = new \XSLTProcessor();
		$xsl = new \DOMDocument();

		if ($xsl->loadXML(file_get_contents($xslFile)) ) {
			$proc->importStyleSheet($xsl);
		}

		$feed = new \DOMDocument();
		$contents = Legacy::getFromUrlOrCache($xmlFile, $days = 0.02);
		if (empty($contents)) {
			return false;
		}
		if ( $feed->loadXML($contents) ) {
			return $proc->transformToXML($feed);
		}

		return false;
	}


	private function limitArticles($content, $limit)
	{
		preg_match_all('|<article.+</article>|Ums', $content, $matches);
		$content = '';
		for ($i = 0; $i < $limit; $i++) {
			$content .= $matches[0][$i];
		}

		return $content;
	}
}
