<?php namespace App\Controller\Admin;

use App\Entity\Language;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class LanguageCrudController extends CrudController {
	protected static $idField = 'code';
	protected static $sortField = 'name';
	protected static $entityClass = Language::class;

	public function configureFields(string $pageName): iterable {
		yield $this->idFieldDisabledOnEdit('code', $pageName);
		yield 'name';
		yield Field::new('nrOfTexts')->hideOnForm();
		yield Field::new('nrOfTranslatedTexts')->hideOnForm();
	}
}
