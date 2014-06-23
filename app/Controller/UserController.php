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
		$user = $this->em()->getUserRepository()->findByUsername($username);
		return array(
			'user' => $user,
			'ratings' => $this->em()->getTextRatingRepository()->getByUser($user),
		);
	}

	public function commentsAction($username, $page) {
		$_REQUEST['username'] = $username;
		$_REQUEST['page'] = $page;

		return $this->legacyPage('Comment');
	}

	public function contribsAction($username, $page) {
		$limit = 50;
		$user = $this->em()->getUserRepository()->findByUsername($username);
		$repo = $this->em()->getUserTextContribRepository();

		return array(
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
	}

	public function readListAction($username, $page) {
		if ($this->getUser()->getUsername() != $username) {
			$user = $this->em()->getUserRepository()->findByToken($username);
			if (!$user) {
				throw $this->createAccessDeniedException();
			}
			$isOwner = false;
		} else {
			$user = $this->em()->getUserRepository()->findByUsername($username);
			$isOwner = true;
		}

		$limit = 50;
		$repo = $this->em()->getUserTextReadRepository();

		return array(
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
			'_cache' => 0,
		);
	}

	public function bookmarksAction($username, $page) {
		if ($this->getUser()->getUsername() != $username) {
			$user = $this->em()->getUserRepository()->findByToken($username);
			if (!$user) {
				throw $this->createAccessDeniedException();
			}
			$isOwner = false;
		} else {
			$user = $this->em()->getUserRepository()->findByUsername($username);
			$isOwner = true;
		}

		$limit = 50;
		$repo = $this->em()->getBookmarkRepository();

		return array(
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
			'_cache' => 0,
		);
	}

	/**
	 * Tell if any of the requested texts are special for the current user
	 * i.e. the user has bookmarked it or read it
	 */
	public function specialTextsAction(Request $request) {
		$this->responseAge = 0;

		if ($this->getUser()->isAnonymous()) {
			throw $this->createAccessDeniedException();
		}

		$texts = $request->get('texts');

		return $this->asJson(array(
			'read' => array_flip($this->em()->getUserTextReadRepository()->getValidTextIds($this->getUser(), $texts)),
			'favorities' => array_flip($this->em()->getBookmarkRepository()->getValidTextIds($this->getUser(), $texts)),
		));
	}

	public function editAction($username) {
		$this->responseAge = 0;

		if ($this->getUser()->getUsername() != $username) {
			throw $this->createAccessDeniedException();
		}

		$styleUrl = '/bundles/app/css/?skin=SKIN&menu=NAV';
		return $this->legacyPage('Settings', array(
			'inline_js' => "
				var nav = '', skin = '';
				function changeStyleSheet() {
					setActiveStyleSheet('$styleUrl'.replace(/SKIN/, skin).replace(/NAV/, nav));
				}"
		));
	}

	public function stylesheetAction() {
		return $this->render('App:User:stylesheet.html.twig', array(
			'stylesheet' => $this->getStylesheet(),
			'extra_stylesheets' => $this->getUser()->getExtraStylesheets(),
			'extra_javascripts' => $this->getUser()->getExtraJavascripts(),
		));
	}

	private function getStylesheet() {
		$url = $this->container->getParameter('style_url');
		if ( ! $url) {
			return false;
		}

		return $url . http_build_query($this->getUser()->getSkinPreference());
	}

}
