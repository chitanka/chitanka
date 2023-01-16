<?php namespace App\Controller\Admin;

use App\Entity\License;

class LicenseCrudController extends CrudController {
	protected static $entityClass = License::class;
}
