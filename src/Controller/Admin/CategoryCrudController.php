<?php namespace App\Controller\Admin;

use App\Entity\Category;

class CategoryCrudController extends CrudController {
	protected static $entityClass = Category::class;

	protected function entityLabelInPlural(): string {
		return 'Categories';
	}
}
