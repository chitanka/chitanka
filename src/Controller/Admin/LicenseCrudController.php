<?php namespace App\Controller\Admin;

use App\Entity\License;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class LicenseCrudController extends CrudController {
	protected static $entityClass = License::class;

	public function configureFields(string $pageName): iterable {
		yield $this->idFieldDisabledOnEdit('code', $pageName);
		yield 'name';
		yield Field::new('fullname')->hideOnIndex();
		yield BooleanField::new('free')->renderAsSwitch(false);
		yield BooleanField::new('copyright')->renderAsSwitch(false);
		yield Field::new('uri')->hideOnIndex();
	}
}
