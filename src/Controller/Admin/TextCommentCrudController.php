<?php namespace App\Controller\Admin;

use App\Entity\TextComment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class TextCommentCrudController extends CrudController {
	protected static $sortField = 'time';
	protected static $sortDirection = 'DESC';
	protected static $entityClass = TextComment::class;

	protected function isCsrfTokenValid(string $id, ?string $token): bool {
		// Disable so that our links from the legacy page work without a token.
		return true;
	}

	public function configureActions(Actions $actions): Actions {
		return $actions->disable(Action::NEW);
	}

	public function configureFields(string $pageName): iterable {
		yield AssociationField::new('text')->hideOnForm();
		yield 'rname';
		yield Field::new('time')->hideOnForm();
		yield Field::new('content')->hideOnIndex();
		yield 'is_shown';
	}
}
