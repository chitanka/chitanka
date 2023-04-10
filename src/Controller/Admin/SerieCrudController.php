<?php namespace App\Controller\Admin;

use App\Entity\Series;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;

class SerieCrudController extends CrudController {
	protected static $sortField = 'name';
	protected static $entityClass = Series::class;

	public function configureActions(Actions $actions): Actions {
		$viewInFrontend = $this->viewInFrontendAction('series_show', function(Series $serie): array {
			return ['slug' => $serie->getSlug()];
		});
		$actions->add(Crud::PAGE_INDEX, $viewInFrontend);
		return $actions;
	}

	public function configureFields(string $pageName): iterable {
		yield 'name';
		yield SlugField::new('slug')->setTargetFieldName('name');
		yield 'origName';
		yield $this->associationFieldWithManyItems('authors')->hideOnIndex();
	}
}
