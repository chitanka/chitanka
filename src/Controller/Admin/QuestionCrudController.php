<?php namespace App\Controller\Admin;

use App\Entity\Question;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class QuestionCrudController extends CrudController {
	protected static $entityClass = Question::class;

	public function configureFields(string $pageName): iterable {
		yield 'question';
		yield TextField::new('answers')->setHelp('help.question.answers');
	}
}
