<?php namespace App\Controller\Admin;

use App\Entity\Person;

class PersonCrudController extends CrudController {
	protected static $entityClass = Person::class;
}
