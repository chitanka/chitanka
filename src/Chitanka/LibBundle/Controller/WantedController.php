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
		if ( rand(0, 1) === 0 /*every second*/ ) {
			$book = $this->getWantedBookRepository()->getRandom();
			$this->view = array(
				'book' => $book,
				'put_link' => (strpos($book->getDescription(), '<a ') === false),
				'show_link_wo_cover' => true
			);
		}

		return $this->display('stripe');
	}

}
