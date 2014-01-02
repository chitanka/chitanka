<?php

namespace Chitanka\LibBundle\Controller;

class TextCommentAdminController extends CRUDController
{
	public function validateCsrfToken($intention)
	{
		// disable for now
		return true;
	}

}
