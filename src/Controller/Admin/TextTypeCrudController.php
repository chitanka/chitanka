<?php namespace App\Controller\Admin;

use App\Entity\TextType;

class TextTypeCrudController extends CrudController {
	protected static $idField = 'code';
	protected static $entityClass = TextType::class;

	/*
	public function configureFields(string $pageName): iterable {
		return [
			IdField::new('id'),
			TextField::new('title'),
			TextEditorField::new('description'),
		];
	}
	*/
}
