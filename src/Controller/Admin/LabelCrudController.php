<?php namespace App\Controller\Admin;

use App\Entity\Label;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class LabelCrudController extends CrudController {
	protected static $entityClass = Label::class;

	public function configureActions(Actions $actions): Actions {
		$viewInFrontend = $this->viewInFrontendAction('texts_by_label', function(Label $label): array {
			return ['slug' => $label->getSlug()];
		});
		$actions->add(Crud::PAGE_INDEX, $viewInFrontend);
		return $actions;
	}

	public function configureFields(string $pageName): iterable {
		yield 'name';
		yield 'slug';
		yield ChoiceField::new('group')
			->setChoices($this->getTranslation()->getLabelGroupChoices());
		yield Field::new('nrOfTexts')->hideOnForm();
		yield Field::new('description')->hideOnIndex();
		yield $this->associationFieldWithManyItems('parent');
		yield Field::new('position')->setHelp('help.label.position');
	}
}
