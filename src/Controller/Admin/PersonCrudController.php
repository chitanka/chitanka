<?php namespace App\Controller\Admin;

use App\Entity\Person;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PersonCrudController extends CrudController {
	protected static $sortField = 'name';
	protected static $entityClass = Person::class;

	public function configureActions(Actions $actions): Actions {
		$viewInFrontend = $this->viewInFrontendAction('person_show', function(Person $person): array {
			return ['slug' => $person->getSlug()];
		});
		return $actions->add(Crud::PAGE_INDEX, $viewInFrontend);
	}

	public function configureFields(string $pageName): iterable {
		$slug = SlugField::new('slug')->setTargetFieldName('name');
		$isAuthor = BooleanField::new('isAuthor')->renderAsSwitch(false);
		$isTranslator = BooleanField::new('isTranslator')->renderAsSwitch(false);
		$info = TextField::new('info')->setHelp('help.wiki_article');
		if ($pageName === Crud::PAGE_INDEX) {
			return ['name', $slug, 'origName', $isAuthor, $isTranslator, $info];
		}

		$country = AssociationField::new('country');
		$person = $this->associationFieldWithManyItems('person', 'Main Person');
		$type = ChoiceField::new('type', 'Person Type')
			->setChoices($this->getTranslation()->getPersonTypeChoices())
			->renderExpanded();
		if (in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT])) {
			return [
				FormField::addTab('General attributes'),
				'name', $slug, 'origName', 'realName', 'orealName', $country, 'isAuthor', 'isTranslator', $info,
				FormField::addTab('Main Person'),
				$type, $person,
			];
		}

		return parent::configureFields($pageName);
	}
}
