<?php namespace App\Controller\Admin;

use App\Entity\Question;

class QuestionCrudController extends CrudController {
	protected static $entityClass = Question::class;
}
