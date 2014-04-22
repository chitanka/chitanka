<?php
namespace App\Controller;

class EmailController extends Controller {

	public function newAction($username) {
		$this->responseAge = 0;

		$_REQUEST['username'] = $username;

		return $this->legacyPage('EmailUser');
	}
}
