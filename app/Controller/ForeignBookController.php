<?php namespace App\Controller;

class ForeignBookController extends Controller {

	public function indexAction() {
		return [
			'books' => $this->em()->getForeignBookRepository()->getLatest(100)
		];
	}

}
