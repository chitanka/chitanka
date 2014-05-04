<?php namespace App\Controller;

class MessageController extends Controller {

	public function indexAction() {
		if ($this->hasFlashMessages()) {
			return $this->display('index');
		}
		return $this->redirect('homepage');
	}
}
