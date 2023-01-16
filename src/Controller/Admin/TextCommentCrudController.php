<?php namespace App\Controller\Admin;

use App\Entity\TextComment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

class TextCommentCrudController extends CrudController {
	protected static $entityClass = TextComment::class;

	protected function isCsrfTokenValid(string $id, ?string $token): bool {
		// Disable so that our links from the legacy page work without a token.
		return true;
	}

	public function configureActions(Actions $actions): Actions {
		return $actions->disable(Action::NEW);
	}
}
