<?php namespace App\Controller\Admin;

use App\Entity\ExternalSite;

class ExternalSiteCrudController extends CrudController {
	protected static $entityClass = ExternalSite::class;
}
