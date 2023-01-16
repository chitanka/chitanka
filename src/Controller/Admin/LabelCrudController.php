<?php namespace App\Controller\Admin;

use App\Entity\Label;

class LabelCrudController extends CrudController {
	protected static $entityClass = Label::class;
}
