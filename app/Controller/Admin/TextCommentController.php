<?php namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;

class TextCommentController extends CRUDController {
	public function validateCsrfToken($intention, Request $request = null) {
		// disable for now
		return true;
	}

}
