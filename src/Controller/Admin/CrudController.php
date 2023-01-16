<?php namespace App\Controller\Admin;

use App\Entity\Entity;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

abstract class CrudController extends AbstractCrudController {

	protected static $idField = 'id';
	protected static $entityClass = Entity::class;
	public static function getEntityFqcn(): string {
		return static::$entityClass;
	}

	public function configureCrud(Crud $crud): Crud {
		$crud->setDefaultSort([static::$idField => 'ASC']);
		$crud->setEntityLabelInPlural($this->entityLabelInPlural());
		$crud->renderContentMaximized();
		return $crud;
	}

	protected function entityLabelInPlural(): string {
		return $this->baseEntityName() . 's';
	}

	protected function baseEntityName(): string  {
		return basename(str_replace('\\', '/', static::$entityClass));
	}
}
