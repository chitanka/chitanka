<?php namespace App\Controller;

use App\Service\ReviewService;

class MainController extends Controller {

	const LAST_BOOKS_LIMIT = 3;
	const LAST_TEXTS_LIMIT = 20;

	public function indexAction() {
		$this->responseAge = 600;

		return $this->display('index', array(
			'siteNotices' => $this->getSiteNoticeRepository()->findForFrontPage(),
			'review' => ReviewService::getReview(true),
			'last_books' => $this->getBookRevisionRepository()->getLatest(self::LAST_BOOKS_LIMIT, 1, false),
			'last_texts' => $this->getTextRevisionRepository()->getLatest(self::LAST_TEXTS_LIMIT, 1, false),
		));
	}

	public function redirectAction($route) {
		return $this->redirect($route, true);
	}

	public function siteboxAction() {
		return $this->render('App:Main:sitebox.html.twig', array(
			'site' => $this->getSiteRepository()->getRandom()
		));
	}

	public function catalogAction($_format) {
		return $this->display("catalog.$_format");
	}

}
