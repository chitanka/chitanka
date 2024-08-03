<?php namespace App\Controller\Admin;

use App\Entity\TextType;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class TextTypeCrudController extends CrudController {
	protected static $idField = 'code';
	protected static $sortField = 'name';
	protected static $entityClass = TextType::class;

	public function configureFields(string $pageName): iterable {
		yield $this->idFieldDisabledOnEdit('code', $pageName);
		yield 'name';
		yield Field::new('description')->hideOnIndex();
		yield Field::new('nrOfTexts')->hideOnForm();
	}
}
