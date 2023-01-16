<?php namespace App\Controller\Admin;

use App\Entity\Text;

class TextCrudController extends CrudController {
	protected static $entityClass = Text::class;
}
