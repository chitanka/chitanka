<?php namespace App\Controller;

class ForeignBookController extends Controller {

	public function indexAction($_format) {
		return array(
			'books' => $this->em()->getForeignBookRepository()->getLatest(100)
		);
	}

}
