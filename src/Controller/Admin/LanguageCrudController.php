<?php namespace App\Controller\Admin;

use App\Entity\Language;

class LanguageCrudController extends CrudController {
	protected static $idField = 'code';
	protected static $entityClass = Language::class;
}
