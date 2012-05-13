<?php

namespace Chitanka\LibBundle\Controller;

class FeaturedBookController extends Controller
{

	public function indexAction($_format)
	{
		$this->view = array(
			'books' => $this->getFeaturedBookRepository()->getLatest(100),
		);
		$this->responseFormat = $_format;

		return $this->display('index');
	}


	public function bookAction()
	{
		$this->view = array(
			'book' => $this->getFeaturedBookRepository()->getRandom()
		);

		return $this->display('book');
	}

}
