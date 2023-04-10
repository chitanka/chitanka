<?php namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class CategoryCrudController extends CrudController {
	protected static $entityClass = Category::class;

	protected function entityLabelInPlural(): string {
		return 'Categories';
	}

	public function configureActions(Actions $actions): Actions {
		$viewInFrontend = $this->viewInFrontendAction('books_by_category', function(Category $category): array {
			return ['slug' => $category->getSlug()];
		});
		$actions->add(Crud::PAGE_INDEX, $viewInFrontend);
		return $actions;
	}

	public function configureFields(string $pageName): iterable {
		yield 'name';
		yield 'slug';
		yield Field::new('nrOfBooks')->hideOnForm();
		yield Field::new('description')->hideOnIndex();
		yield $this->associationFieldWithManyItems('parent');
	}
}
