<?php namespace App\Controller;

use App\Pagination\Pager;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller {
	public function personalToolsAction() {
		$this->responseAge = 0;

		return $this->render('App:User:personal_tools.html.twig', array(
			'_user' => $this->getUser()
		));
	}

	public function showAction($username) {
		$this->responseAge = 0;

		$_REQUEST['username'] = $username;

		return $this->legacyPage('User');
	}

	public function pageAction($username) {
		$this->responseAge = 0;

		$_REQUEST['username'] = $username;

		return $this->legacyPage('EditUserPage');
	}

	public function ratingsAction($username) {
		$user = $this->getUserRepository()->findByUsername($username);
		$ratings = $this->getTextRatingRepository()->getByUser($user);
		return $this->display('ratings', array(
			'user' => $user,
			'ratings' => $ratings,
		));
	}

	public function commentsAction($username, $page) {
		$_REQUEST['username'] = $username;
		$_REQUEST['page'] = $page;

		return $this->legacyPage('Comment');
	}

	public function contribsAction($username, $page) {
		$limit = 50;
		$user = $this->getUserRepository()->findByUsername($username);
		$repo = $this->getUserTextContribRepository();

		$this->view = array(
			'user' => $user,
			'contribs' => $repo->getByUser($user, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countByUser($user)
			)),
			'route' => 'user_contribs',
			'route_params' => array('username' => $username),
		);

		return $this->display('contribs');
	}

	public function readListAction($username, $page) {
		$this->responseAge = 0;

		if ($this->getUser()->getUsername() != $username) {
			$user = $this->getUserRepository()->findByToken($username);
			if ( ! $user) {
				return $this->notAllowed();
			}
			$isOwner = false;
		} else {
			$user = $this->getUserRepository()->findByUsername($username);
			$isOwner = true;
		}

		$limit = 50;
		$repo = $this->getUserTextReadRepository();

		$this->view = array(
			'user' => $user,
			'is_owner' => $isOwner,
			'read_texts' => $repo->getByUser($user, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countByUser($user)
			)),
			'route' => 'user_read_list',
			'route_params' => array('username' => $username),
		);

		return $this->display('read_list');
	}

	public function bookmarksAction($username, $page) {
		$this->responseAge = 0;

		if ($this->getUser()->getUsername() != $username) {
			$user = $this->getUserRepository()->findByToken($username);
			if ( ! $user) {
				return $this->notAllowed();
			}
			$isOwner = false;
		} else {
			$user = $this->getUserRepository()->findByUsername($username);
			$isOwner = true;
		}

		$limit = 50;
		$repo = $this->getBookmarkRepository();

		$this->view = array(
			'user' => $user,
			'is_owner' => $isOwner,
			'bookmarks' => $repo->getByUser($user, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countByUser($user)
			)),
			'route' => 'user_bookmarks',
			'route_params' => array('username' => $username),
		);

		return $this->display('bookmarks');
	}

	/**
	 * Tell if any of the requested texts are special for the current user
	 * i.e. the user has bookmarked it or read it
	 */
	public function specialTextsAction(Request $request) {
		$this->responseAge = 0;

		if ($this->getUser()->isAnonymous()) {
			return $this->notAllowed();
		}

		$texts = $request->get('texts');

		return $this->displayJson(array(
			'read' => array_flip($this->getUserTextReadRepository()->getValidTextIds($this->getUser(), $texts)),
			'favorities' => array_flip($this->getBookmarkRepository()->getValidTextIds($this->getUser(), $texts)),
		));
	}

	public function editAction($username) {
		$this->responseAge = 0;

		if ($this->getUser()->getUsername() != $username) {
			return $this->notAllowed();
		}

		$styleUrl = '/bundles/app/css/?skin=SKIN&menu=NAV';
		$this->view['inline_js'] = <<<EOS
	var nav = "", skin = "";
	function changeStyleSheet() {
		setActiveStyleSheet("$styleUrl".replace(/SKIN/, skin).replace(/NAV/, nav));
	}
EOS;

		return $this->legacyPage('Settings');
	}

	public function stylesheetAction() {
		$this->responseAge = 0;

		return $this->render('App:User:stylesheet.html.twig', array(
			'stylesheet' => $this->getStylesheet(),
			'extra_stylesheets' => $this->getUser()->getExtraStylesheets(),
			'extra_javascripts' => $this->getUser()->getExtraJavascripts(),
		));
	}
}
