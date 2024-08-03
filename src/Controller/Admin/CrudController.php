<?php namespace App\Controller\Admin;

use App\Entity\Entity;
use App\Service\Translation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class CrudController extends AbstractCrudController {

	protected static $idField = 'id';
	protected static $sortField = 'id';
	protected static $sortDirection = 'ASC';
	protected static $entityClass = Entity::class;
	public static function getEntityFqcn(): string {
		return static::$entityClass;
	}

	public function configureCrud(Crud $crud): Crud {
		$crud->setDefaultSort([static::$sortField => static::$sortDirection]);
		$crud->setEntityLabelInPlural($this->entityLabelInPlural());
		$crud->renderContentMaximized();
		$crud->showEntityActionsInlined();
		return $crud;
	}

	protected function entityLabelInPlural(): string {
		return $this->baseEntityName() . 's';
	}

	protected function baseEntityName(): string  {
		return basename(str_replace('\\', '/', static::$entityClass));
	}

	protected function getTranslation(): Translation {
		return new Translation();
	}

	protected function idFieldDisabledOnEdit(string $name, string $pageName): FieldInterface {
		$field = Field::new($name);
		if ($pageName === Crud::PAGE_EDIT) {
			$field->setDisabled();
		}
		return $field;
	}

	protected function viewInFrontendAction(string $route, $routeParameters): Action {
		return Action::new('frontend', 'library_link_action_show', 'fa fa-tv')
		    ->displayAsLink()
			->linkToRoute($route, $routeParameters);

	}

	protected function associationFieldWithManyItems(string $name, string $label = null): AssociationField {
		return AssociationField::new($name, $label)->autocomplete();
	}

	protected function collectionField(string $name, string $class, string $label = null): CollectionField {
		# by_reference : false => Needed to ensure that addXxx() and removeXxx() will be called during the flush.
		# See (last lines) : http://symfony.com/doc/master/reference/forms/types/collection.html#by-reference
		return CollectionField::new($name, $label)->setEntryType($class)->setFormTypeOption('by_reference', false);
	}

	protected function redirectToIndex(AdminContext $context): RedirectResponse {
		$url = $context->getReferrer()
			?? $this->adminUrlGenerator()->setAction(Action::INDEX)->generateUrl();
		return $this->redirect($url);
	}

	protected function adminUrlGenerator(): AdminUrlGenerator {
		return $this->container->get(AdminUrlGenerator::class)->setController(static::class);
	}
}
