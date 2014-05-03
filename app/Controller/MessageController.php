<?php namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;

class MessageController extends Controller {

	public function indexAction(Request $request) {
		if ($request->getSession()->getFlashBag()->peekAll() == array()) {
			return $this->redirect('homepage');
		}
		return $this->display('index');
	}
}
