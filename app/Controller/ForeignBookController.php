<?php namespace App\Controller;

class ForeignBookController extends Controller {

	public function indexAction() {
		return array(
			'books' => $this->em()->getForeignBookRepository()->getLatest(100)
		);
	}

}
