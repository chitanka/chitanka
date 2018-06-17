<?php namespace App\Controller;

use App\Pagination\Pager;
use App\Service\System;
use App\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller {

	const PAGE_COUNT_DEFAULT = 50;
	const PAGE_COUNT_LIMIT = 500;

	public function personalToolsAction() {
		if (!$this->container->getParameter('allow_user_registration')) {
			return $this->asText('');
		}
		return $this->render('App:User:personal_tools.html.twig', [
			'_user' => $this->getUser(),
			'_cache' => 0,
		]);
	}

	/**
	 * @Route("/user/close-account", name="user_close_account")
	 */
	public function closeAccountAction(Request $request) {
		$user = $this->getUser();
		if ($user->isAnonymous()) {
			return $this->redirectToRoute('login');
		}
		$form = $this->createFormBuilder()->getForm();
		$form->handleRequest($request);
		if ($form->isSubmitted()) {
			$system = new System($this->em());
			if ($system->closeUserAccount($user)) {
				return $this->redirectWithNotice('Профилът ви беше закрит.');
			}
			$this->flashes()->addError('Действието не успя да бъде извършено. Изчакайте малко и опитайте отново.');
		}
		return [
			'form' => $form->createView(),
		];
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
		$userService = new UserService($user, $this->getParameter('content_dir'));

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

	public function contribsAction(Request $request, $username, $page) {
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$user = $this->em()->getUserRepository()->findByUsername($username);
		$repo = $this->em()->getUserTextContribRepository();
		return [
			'user' => $user,
			'contribs' => $repo->getByUser($user, $page, $limit),
			'pager'    => new Pager($page, $repo->countByUser($user), $limit),
			'route' => 'user_contribs',
			'route_params' => ['username' => $username],
		];
	}

	public function readListAction(Request $request, $username, $page) {
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

		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$repo = $this->em()->getUserTextReadRepository();

		return [
			'user' => $user,
			'is_owner' => $isOwner,
			'read_texts' => $repo->getByUser($user, $page, $limit),
			'pager'    => new Pager($page, $repo->countByUser($user), $limit),
			'route' => 'user_read_list',
			'route_params' => ['username' => $username],
			'_cache' => 0,
		];
	}

	public function bookmarksAction(Request $request, $username, $page) {
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

		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$repo = $this->em()->getBookmarkRepository();

		return [
			'user' => $user,
			'is_owner' => $isOwner,
			'bookmarks' => $repo->getByUser($user, $page, $limit),
			'pager'    => new Pager($page, $repo->countByUser($user), $limit),
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
