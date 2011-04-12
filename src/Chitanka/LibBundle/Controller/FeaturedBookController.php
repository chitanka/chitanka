<?php

namespace Chitanka\LibBundle\Controller;

class FeaturedBookController extends Controller
{

	public function indexAction()
	{
		$this->view = array(
			'books' => $this->getRepository('FeaturedBook')->getLatest(100)
		);

		return $this->display('index');
	}


	public function bookAction()
	{
		$this->view = array(
			'book' => $this->getRepository('FeaturedBook')->getRandom()
		);

		return $this->display('book');
	}

}
