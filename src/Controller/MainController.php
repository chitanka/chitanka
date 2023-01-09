<?php namespace App\Controller;

use App\Feed\ForumFeed;
use App\Feed\LiternewsFeed;
use App\Persistence\BookRevisionRepository;
use App\Persistence\SearchStringRepository;
use App\Persistence\SiteNoticeRepository;
use App\Persistence\TextCommentRepository;
use App\Persistence\TextRevisionRepository;

class MainController extends Controller {

	const LATEST_BOOKS_LIMIT = 6;
	const LATEST_TEXTS_LIMIT = 20;
	const LATEST_SEARCHES_LIMIT = 50;
	const LATEST_COMMENTS_LIMIT = 5;
	const LATEST_LITERNEWS_LIMIT = 3;
	const LATEST_FORUM_POSTS_LIMIT = 5;

	public function indexAction(
		SiteNoticeRepository $siteNoticeRepository,
		BookRevisionRepository $bookRevisionRepository,
		TextRevisionRepository $textRevisionRepository,
		SearchStringRepository $searchStringRepository,
		TextCommentRepository $textCommentRepository,
		array $mainPageSections,
		string $liternewsFeedUrl,
		string $forumFeedUrl
	) {
		$vars = [
			'siteNotices' => $siteNoticeRepository->findForFrontPage(),
			'_cache' => 600,
		];
		$sections = $mainPageSections;
		if (in_array('books', $sections)) {
			$vars['books'] = $bookRevisionRepository->getLatest(self::LATEST_BOOKS_LIMIT);
		}
		if (in_array('texts', $sections)) {
			$vars['texts'] = $textRevisionRepository->getLatest(self::LATEST_TEXTS_LIMIT);
		}
		if (in_array('liter_posts', $sections)) {
			$vars['liter_posts'] = (new LiternewsFeed($liternewsFeedUrl))->fetchLatest(self::LATEST_LITERNEWS_LIMIT);
		}
		if (in_array('searches', $sections)) {
			$vars['searches'] = $searchStringRepository->getLatest(self::LATEST_SEARCHES_LIMIT);
		}
		if (in_array('comments', $sections)) {
			$vars['comments'] = $textCommentRepository->getLatest(self::LATEST_COMMENTS_LIMIT);
		}
		if (in_array('forum_posts', $sections)) {
			$vars['forum_posts'] = (new ForumFeed($forumFeedUrl))->fetchLatest(self::LATEST_FORUM_POSTS_LIMIT);
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
