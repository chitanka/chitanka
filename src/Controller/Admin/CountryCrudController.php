<?php namespace App\Controller\Admin;

use App\Entity\Country;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class CountryCrudController extends CrudController {
	protected static $idField = 'code';
	protected static $sortField = 'name';
	protected static $entityClass = Country::class;

	protected function entityLabelInPlural(): string {
		return 'Countries';
	}

	public function configureFields(string $pageName): iterable {
		yield $this->idFieldDisabledOnEdit('code', $pageName);
		yield 'name';
		yield Field::new('nrOfAuthors')->hideOnForm();
		yield Field::new('nrOfTranslators')->hideOnForm();
	}
}
