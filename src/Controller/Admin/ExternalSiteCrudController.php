<?php namespace App\Controller\Admin;

use App\Entity\ExternalSite;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class ExternalSiteCrudController extends CrudController {
	protected static $sortField = 'name';
	protected static $entityClass = ExternalSite::class;

	public function configureFields(string $pageName): iterable {
		yield 'name';
		yield Field::new('url')->setHelp('help.externalsite.url');
		yield ChoiceField::new('mediaType')->setChoices(array_combine(ExternalSite::MEDIA_TYPES, ExternalSite::MEDIA_TYPES));
	}
}
