<?php namespace App\Controller;

class MessageController extends Controller {

	public function indexAction() {
		if ($this->flashes()->hasMessages()) {
			return $this->display('index');
		}
		return $this->redirect('homepage');
	}
}
