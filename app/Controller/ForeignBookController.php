<?php namespace App\Controller;

class ForeignBookController extends Controller {

	public function indexAction($_format) {
		return $this->display("index.$_format", array(
			'books' => $this->em()->getForeignBookRepository()->getLatest(100)
		));
	}

}
