<?php

namespace Chitanka\LibBundle\Controller;

class ForeignBookController extends Controller
{

	public function indexAction($_format)
	{
		$this->view = array(
			'books' => $this->getRepository('ForeignBook')->getLatest(100)
		);
		$this->responseFormat = $_format;

		return $this->display('index');
	}


	public function bookAction()
	{
		$this->view = array(
			'book' => $this->getRepository('ForeignBook')->getRandom()
		);

		return $this->display('book');
	}

}
