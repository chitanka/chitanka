<?php namespace App\Controller;

use App\Feed\ForumFeed;
use App\Feed\LiternewsFeed;
use App\Service\ReviewService;

class MainController extends Controller {

	const LATEST_BOOKS_LIMIT = 3;
	const LATEST_TEXTS_LIMIT = 20;
	const LATEST_SEARCHES_LIMIT = 50;
	const LATEST_COMMENTS_LIMIT = 5;
	const LATEST_LITERNEWS_LIMIT = 3;
	const LATEST_FORUM_POSTS_LIMIT = 5;

	public function indexAction() {
		return [
			'siteNotices' => $this->em()->getSiteNoticeRepository()->findForFrontPage(),
			'review' => ReviewService::getReview(true),
			'foreign_book' => $this->em()->getForeignBookRepository()->getRandom(),
			'featured_book' => $this->em()->getFeaturedBookRepository()->getRandom(),
			'latest_books' => $this->em()->getBookRevisionRepository()->getLatest(self::LATEST_BOOKS_LIMIT, 1, false),
			'latest_texts' => $this->em()->getTextRevisionRepository()->getLatest(self::LATEST_TEXTS_LIMIT, 1, false),
			'latest_searches' => $this->em()->getSearchStringRepository()->getLatest(self::LATEST_SEARCHES_LIMIT),
			'latest_comments' => $this->em()->getTextCommentRepository()->getLatest(self::LATEST_COMMENTS_LIMIT),
			'latest_liternews' => LiternewsFeed::fetchLatest(self::LATEST_LITERNEWS_LIMIT),
			'latest_forum_posts' => ForumFeed::fetchLatest(self::LATEST_FORUM_POSTS_LIMIT),
			'_cache' => 600,
		];
	}

	public function catalogAction() {
		return [];
	}

	public function redirectAction($route) {
		return $this->redirectToRoute($route, [], true);
	}

	public function siteboxAction() {
		return $this->render('App:Main:sitebox.html.twig', [
			'site' => $this->em()->getSiteRepository()->getRandom()
		]);
	}

}
