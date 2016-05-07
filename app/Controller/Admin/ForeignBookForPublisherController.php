<?php namespace App\Controller\Admin;

use App\Entity\User;

class ForeignBookForPublisherController extends CRUDController {

	protected function isAuthorized() {
		return $this->getUser()->inGroup(User::GROUP_FOREIGN_BOOK_PUBLISHER);
	}
}
