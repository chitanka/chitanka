<?php namespace App\Controller;

use App\Service\ForumService;
use App\Service\LiternewsService;
use App\Service\ReviewService;

class MainController extends Controller {

	const LATEST_BOOKS_LIMIT = 3;
	const LATEST_TEXTS_LIMIT = 20;
	const LATEST_SEARCHES_LIMIT = 50;
	const LATEST_COMMENTS_LIMIT = 5;
	const LATEST_LITERNEWS_LIMIT = 3;
	const LATEST_FORUM_POSTS_LIMIT = 5;

	public function indexAction() {
		$this->responseAge = 600;

		return $this->display('index', array(
			'siteNotices' => $this->getSiteNoticeRepository()->findForFrontPage(),
			'review' => ReviewService::getReview(true),
			'foreign_book' => $this->getForeignBookRepository()->getRandom(),
			'featured_book' => $this->getFeaturedBookRepository()->getRandom(),
			'latest_books' => $this->getBookRevisionRepository()->getLatest(self::LATEST_BOOKS_LIMIT, 1, false),
			'latest_texts' => $this->getTextRevisionRepository()->getLatest(self::LATEST_TEXTS_LIMIT, 1, false),
			'latest_searches' => $this->getSearchStringRepository()->getLatest(self::LATEST_SEARCHES_LIMIT),
			'latest_comments' => $this->getTextCommentRepository()->getLatest(self::LATEST_COMMENTS_LIMIT),
			'latest_liternews' => LiternewsService::fetchLatest(self::LATEST_LITERNEWS_LIMIT),
			'latest_forum_posts' => ForumService::fetchLatest(self::LATEST_FORUM_POSTS_LIMIT),
		));
	}

	public function catalogAction($_format) {
		return $this->display("catalog.$_format");
	}

	public function redirectAction($route) {
		return $this->redirect($route, true);
	}

	public function siteboxAction() {
		return $this->render('App:Main:sitebox.html.twig', array(
			'site' => $this->getSiteRepository()->getRandom()
		));
	}

}
