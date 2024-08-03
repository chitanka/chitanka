<?php namespace App\Controller\Admin;

use App\Entity\Sequence;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

class SequenceCrudController extends CrudController {
	protected static $entityClass = Sequence::class;

	public function configureActions(Actions $actions): Actions {
		$viewInFrontend = $this->viewInFrontendAction('sequence_show', function(Sequence $sequence): array {
			return ['slug' => $sequence->getSlug()];
		});
		$actions->add(Crud::PAGE_INDEX, $viewInFrontend);
		return $actions;
	}

	public function configureFields(string $pageName): iterable {
		yield 'name';
		yield SlugField::new('slug')->setTargetFieldName('name');
		yield Field::new('publisher');
		yield Field::new('nrOfBooks')->hideOnForm();
		yield Field::new('isSeqnrVisible')->hideOnIndex();
		yield TextEditorField::new('annotation')->hideOnIndex();
	}
}
