<?php namespace App\Controller;

use App\Feed\ForumFeed;
use App\Feed\LiternewsFeed;

class MainController extends Controller {

	const LATEST_BOOKS_LIMIT = 3;
	const LATEST_TEXTS_LIMIT = 20;
	const LATEST_SEARCHES_LIMIT = 50;
	const LATEST_COMMENTS_LIMIT = 5;
	const LATEST_LITERNEWS_LIMIT = 3;
	const LATEST_FORUM_POSTS_LIMIT = 5;

	public function indexAction() {
		$vars = [
			'siteNotices' => $this->em()->getSiteNoticeRepository()->findForFrontPage(),
			'dashboard' => $this->container->getParameter('main.dashboard'),
			'_cache' => 600,
		];
		$sections = $this->container->getParameter('main.sections');
		if (in_array('books', $sections)) {
			$vars['books'] = $this->em()->getBookRevisionRepository()->getLatest(self::LATEST_BOOKS_LIMIT, 1, false);
		}
		if (in_array('texts', $sections)) {
			$vars['texts'] = $this->em()->getTextRevisionRepository()->getLatest(self::LATEST_TEXTS_LIMIT, 1, false);
		}
		if (in_array('liter_posts', $sections)) {
			$vars['liter_posts'] = LiternewsFeed::fetchLatest(self::LATEST_LITERNEWS_LIMIT);
		}
		if (in_array('searches', $sections)) {
			$vars['searches'] = $this->em()->getSearchStringRepository()->getLatest(self::LATEST_SEARCHES_LIMIT);
		}
		if (in_array('comments', $sections)) {
			$vars['comments'] = $this->em()->getTextCommentRepository()->getLatest(self::LATEST_COMMENTS_LIMIT);
		}
		if (in_array('forum_posts', $sections)) {
			$vars['forum_posts'] = ForumFeed::fetchLatest(self::LATEST_FORUM_POSTS_LIMIT);
		}
		return $vars;
	}

	public function catalogAction() {
		return [];
	}

	public function redirectAction($route) {
		return $this->redirectToRoute($route, []);
	}

}
