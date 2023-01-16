<?php namespace App\Controller\Admin;

use App\Entity\Country;

class CountryCrudController extends CrudController {
	protected static $idField = 'code';
	protected static $entityClass = Country::class;

	protected function entityLabelInPlural(): string {
		return 'Countries';
	}
}
