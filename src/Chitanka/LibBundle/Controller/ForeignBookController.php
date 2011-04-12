<?php

namespace Chitanka\LibBundle\Controller;

class ForeignBookController extends Controller
{

	public function indexAction()
	{
		$this->view = array(
			'books' => $this->getRepository('ForeignBook')->getLatest(100)
		);

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
