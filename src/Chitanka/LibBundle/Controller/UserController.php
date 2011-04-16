<?php

namespace Chitanka\LibBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Chitanka\LibBundle\Pagination\Pager;

class UserController extends Controller
{
	public function personalToolsAction()
	{
		$this->responseAge = 0;

		return $this->display('personal_tools');
	}


	public function showAction($username)
	{
		$this->responseAge = 0;

		$_REQUEST['username'] = $username;

		$this->view['js_extra'][] = 'jquery-tooltip';
		$this->view['inline_js'] = '$(".tooltip").tooltip({showURL: false, showBody: "<br />" });';

		return $this->legacyPage('User');
	}

	public function pageAction($username)
	{
		$this->responseAge = 0;

		$_REQUEST['username'] = $username;

		return $this->legacyPage('EditUserPage');
	}


	public function ratingsAction($username)
	{
		$_REQUEST['username'] = $username;

		return $this->legacyPage('Textrating');
	}


	public function commentsAction($username)
	{
		$_REQUEST['username'] = $username;

		return $this->legacyPage('Comment');
	}


	public function contribsAction($username, $page)
	{
		$limit = 50;
		$user = $this->getRepository('User')->findOneby(array('username' => $username));
		$repo = $this->getRepository('UserTextContrib');

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


	public function readListAction($username, $page)
	{
		if ($this->getUser()->getUsername() != $username) {
			throw new HttpException(401);
		}

		$limit = 50;
		$user = $this->getRepository('User')->findOneBy(array('username' => $username));
		$repo = $this->getRepository('UserTextRead');

		$this->view = array(
			'user' => $user,
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


	public function bookmarksAction($username, $page)
	{
		if ($this->getUser()->getUsername() != $username) {
			throw new HttpException(401);
		}

		$limit = 50;
		$user = $this->getRepository('User')->findOneBy(array('username' => $username));
		$repo = $this->getRepository('Bookmark');

		$this->view = array(
			'user' => $user,
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
	public function specialTextsAction()
	{
		if ($this->getUser()->isAnonymous()) {
			throw new HttpException(401);
		}

		$this->responseAge = 0;

		$texts = $this->get('request')->query->get('texts');

		return $this->displayJson(array(
			'read' => array_flip($this->getRepository('UserTextRead')->getValidTextIds($this->getUser(), $texts)),
			'favorities' => array_flip($this->getRepository('Bookmark')->getValidTextIds($this->getUser(), $texts)),
		));
	}


	public function editAction($username)
	{
		if ($this->getUser()->getUsername() != $username) {
			throw new HttpException(401);
		}

		$this->responseAge = 0;

		$styleUrl = '/css/SKIN,NAV.css';
		$this->view['inline_js'] = <<<EOS
	var nav = "", skin = "";
	function changeStyleSheet() {
		setActiveStyleSheet("$styleUrl".replace(/SKIN/, skin).replace(/NAV/, nav));
	}
EOS;

		return $this->legacyPage('Settings');
	}

	public function emailAction($username)
	{
		$_REQUEST['username'] = $username;

		return $this->legacyPage('EmailUser');
	}


	public function stylesheetAction()
	{
		$this->responseAge = 0;

		return $this->render('LibBundle:User:stylesheet.html.twig', array(
			'stylesheet' => $this->getStylesheet()
		));
	}
}
