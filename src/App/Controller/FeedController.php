<?php namespace App\Controller;

use App\Service\FeedService;
use App\Service\ReviewService;

class FeedController extends Controller {

	protected $responseAge = 1800;

	public function lastLiternewsAction($limit = 3) {
		$feedUrl = 'http://planet.chitanka.info/atom.xml';
		$xsl = __DIR__.'/../Resources/transformers/forum-atom-compact.xsl';

		$feedService = new FeedService();
		$content = $feedService->fetchAndTransform($feedUrl, $xsl);
		if ($content === false) {
			return $this->displayText('<p class="error">Неуспех при вземането на последните литературни новини.</p>');
		}

		$content = $feedService->limitArticles($content, $limit);
		$content = $feedService->cleanup($content);

		return $this->displayText($content);
	}

	public function lastInsideNewsAction($limit = 8) {
		$feedUrl = 'http://identi.ca/api/statuses/user_timeline/127745.atom';
		$xsl = __DIR__.'/../Resources/transformers/forum-atom-compact.xsl';

		$feedService = new FeedService();
		$content = $feedService->fetchAndTransform($feedUrl, $xsl);
		if ($content === false) {
			return $this->displayText('<p class="error">Неуспех при вземането на последните съобщения от identi.ca.</p>');
		}

		$content = $feedService->limitArticles($content, $limit);

		return $this->displayText($content);
	}

	public function lastForumPostsAction($limit = 5) {
		$feedUrl = 'http://forum.chitanka.info/feed.php?c=' . $limit;
		$xsl = __DIR__.'/../Resources/transformers/forum-atom-compact.xsl';

		$feedService = new FeedService();
		$content = $feedService->fetchAndTransform($feedUrl, $xsl);
		if ($content === false) {
			return $this->displayText('<p class="error">Неуспех при вземането на последните форумни съобщения.</p>');
		}

		$content = $feedService->cleanupForumFeed($content);

		return $this->displayText($content);
	}

	public function reviewsAction() {
		$reviews = ReviewService::getReviews();
		if (empty($reviews)) {
			return $this->displayText('No reviews found');
		}
		return $this->display('Review:index', array(
			'reviews' => $reviews,
		));
	}

}
