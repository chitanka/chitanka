<?php

namespace Chitanka\LibBundle\Controller;

class MainController extends Controller
{

	public function indexAction()
	{
		$this->responseAge = 600;

		return $this->display('index');
	}


	public function aboutAction()
	{
		return $this->legacyPage('About');
	}

	public function rulesAction()
	{
		return $this->legacyPage('Rules');
	}

	public function blacklistAction()
	{
		return $this->legacyPage('Blacklist');
	}

	public function defaultAction()
	{
		return $this->notFoundAction();
	}

	public function notFoundAction()
	{
		$response = $this->display('not_found');
		$response->setStatusCode(404);

		return $response;
	}

	public function redirectAction($route)
	{
		return $this->redirect($route, true);
	}

	public function siteboxAction()
	{
		$data = array(
			'site' => $this->getSiteRepository()->getRandom()
		);

		return $this->render('LibBundle:Main:sitebox.html.twig', $data);
	}


	public function lastBooksAction($limit = 3)
	{
		$this->view = array(
			'revisions' => $this->getBookRevisionRepository()->getLatest($limit, false),
		);

		return $this->display('last_books');
	}

	public function lastTextsAction($limit = 20)
	{
		$this->view = array(
			'revisions' => $this->getTextRevisionRepository()->getLatest($limit, false),
		);

		return $this->display('last_texts');
	}


	public function catalogAction($_format)
	{
		return $this->display("catalog.$_format");
	}

}
