<?php namespace App\Controller;

class MessageController extends Controller {

	public function indexAction() {
		if ($this->flashes()->hasMessages()) {
			return [];
		}
		return $this->redirectToRoute('homepage');
	}
}
