<?php namespace App\Controller;

class ForeignBookController extends Controller {

	public function indexAction($_format) {
		return $this->display("index.$_format", array(
			'books' => $this->getForeignBookRepository()->getLatest(100)
		));
	}

}
