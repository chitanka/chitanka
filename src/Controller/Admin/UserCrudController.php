<?php namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class UserCrudController extends CrudController {
	protected static $entityClass = User::class;

	public function configureFields(string $pageName): iterable {
		yield 'username';
		yield 'email';
		yield Field::new('registration')->hideOnForm();
		yield Field::new('touched')->hideOnForm();
		yield Field::new('realname')->hideOnIndex();
		yield Field::new('email')->hideOnIndex();
		yield Field::new('allowemail')->hideOnIndex();
		yield ChoiceField::new('groups')
			->allowMultipleChoices()
			->setChoices($this->getTranslation()->getUserGroupChoices())
			->hideOnIndex();
		yield Field::new('news')->hideOnIndex();
		yield Field::new('token')->hideOnIndex();
	}
}
