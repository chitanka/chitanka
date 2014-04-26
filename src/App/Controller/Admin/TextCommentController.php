<?php namespace App\Controller\Admin;

class TextCommentController extends CRUDController {
	public function validateCsrfToken($intention) {
		// disable for now
		return true;
	}

}
