<?php

namespace Chitanka\LibBundle\Controller;

class WantedController extends Controller
{
	public function indexAction()
	{
		$this->view['books'] = $this->getWantedBookRepository()->findAll();

		return $this->display('index');
	}

	public function stripeAction()
	{
		if ( rand(0, 2) === 0 /*every third*/ ) {
			$book = $this->getWantedBookRepository()->getRandom();
			$this->view = array(
				'book' => $book,
				'put_link' => (strpos($book->getDescription(), '<a ') === false),
			);
		}

		return $this->display('stripe');
	}

}
