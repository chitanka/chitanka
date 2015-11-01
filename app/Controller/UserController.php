<?php namespace App\Controller;

use App\Pagination\Pager;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller {

	public function personalToolsAction() {
		return $this->render('App:User:personal_tools.html.twig', [
			'_user' => $this->getUser(),
			'_cache' => 0,
		]);
	}

	public function showAction($username) {
		$this->responseAge = 0;

		$_REQUEST['username'] = $username;

		return $this->legacyPage('User');
	}

	public function pageAction(Request $request, $username) {
		if ($this->getUser()->getUsername() != $username) {
			throw $this->createAccessDeniedException();
		}

		$user = $this->em()->getUserRepository()->findByUsername($username);
		$userService = new UserService($user, $this->container);

		if ($request->isMethod('POST')) {
			$userService->saveUserPageContent($request->request->get("userpage"));
			return $this->urlRedirect($this->generateUrl(
				'user_show',
				['username' => $username]
			));
		}

		return [
			'user' => $user,
			'userpage' => $userService->getUserPageContent(),
			'_cache' => 0,
		];
	}

	public function ratingsAction($username) {
		$user = $this->em()->getUserRepository()->findByUsername($username);
		return [
			'user' => $user,
			'ratings' => $this->em()->getTextRatingRepository()->getByUser($user),
		];
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

		return [
			'user' => $user,
			'contribs' => $repo->getByUser($user, $page, $limit),
			'pager'    => new Pager([
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countByUser($user)
			]),
			'route' => 'user_contribs',
			'route_params' => ['username' => $username],
		];
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

		return [
			'user' => $user,
			'is_owner' => $isOwner,
			'read_texts' => $repo->getByUser($user, $page, $limit),
			'pager'    => new Pager([
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countByUser($user)
			]),
			'route' => 'user_read_list',
			'route_params' => ['username' => $username],
			'_cache' => 0,
		];
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

		return [
			'user' => $user,
			'is_owner' => $isOwner,
			'bookmarks' => $repo->getByUser($user, $page, $limit),
			'pager'    => new Pager([
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countByUser($user)
			]),
			'route' => 'user_bookmarks',
			'route_params' => ['username' => $username],
			'_cache' => 0,
		];
	}

	/**
	 * Tell if any of the requested texts are special for the current user
	 * i.e. the user has bookmarked it or read it
	 */
	public function specialTextsAction(Request $request) {
		if ($this->getUser()->isAnonymous()) {
			throw $this->createAccessDeniedException();
		}

		$texts = $request->get('texts');

		return $this->asJson([
			'read' => array_flip($this->em()->getUserTextReadRepository()->getValidTextIds($this->getUser(), $texts)),
			'favorities' => array_flip($this->em()->getBookmarkRepository()->getValidTextIds($this->getUser(), $texts)),
			'_cache' => 0,
		]);
	}

	public function editAction($username) {
		$this->responseAge = 0;

		if ($this->getUser()->getUsername() != $username) {
			throw $this->createAccessDeniedException();
		}

		$styleUrl = $this->container->getParameter('style_url') . 'skin=SKIN&menu=NAV';
		return $this->legacyPage('Settings', [
			'inline_js' => "
				function changeStyleSheet(skin, nav) {
					var url = '$styleUrl'.replace(/SKIN/, skin).replace(/NAV/, nav);
					$('<link rel=\"stylesheet\" type=\"text/css\" href=\"'+url+'\">').appendTo('head');
				}"
		]);
	}

	public function stylesheetAction() {
		return $this->render('App:User:stylesheet.html.twig', [
			'stylesheet' => $this->getStylesheet(),
			'extra_stylesheets' => $this->getUser()->getExtraStylesheets(),
			'extra_javascripts' => $this->getUser()->getExtraJavascripts(),
		]);
	}

	private function getStylesheet() {
		$url = $this->container->getParameter('style_url');
		if ( ! $url) {
			return false;
		}

		return $url . http_build_query($this->getUser()->getSkinPreference());
	}

}
