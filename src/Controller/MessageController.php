<?php namespace App\Controller;

class MessageController extends Controller {

	public function indexAction() {
		if ($this->flashes()->hasMessages()) {
			return [
				'_cache' => 0,
			];
		}
		return $this->redirectToRoute('homepage');
	}
}
