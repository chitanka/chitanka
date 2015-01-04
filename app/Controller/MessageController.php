<?php namespace App\Controller;

class MessageController extends Controller {

	public function indexAction() {
		if ($this->flashes()->hasMessages()) {
			return array();
		}
		return $this->redirectToRoute('homepage');
	}
}
