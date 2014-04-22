<?php

namespace Chitanka\LibBundle\Controller;

class FeaturedBookController extends Controller
{

	public function indexAction($_format)
	{
		$this->view = array(
			'books' => $this->getFeaturedBookRepository()->getLatest(100),
		);

		return $this->display("index.$_format");
	}


	public function bookAction() {
		$this->view = array(
			'book' => $this->getFeaturedBookRepository()->getRandom()
		);

		return $this->render('App:FeaturedBook:book.html.twig', $this->view);
	}

}
