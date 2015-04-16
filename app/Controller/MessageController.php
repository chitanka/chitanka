<?php namespace App\Controller;

class MessageController extends Controller {

	public function indexAction() {
		$this->responseAge = 0;

		if ($this->flashes()->hasMessages()) {
			return [];
		}
		return $this->redirectToRoute('homepage');
	}
}
