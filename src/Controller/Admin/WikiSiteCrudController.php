<?php namespace App\Controller\Admin;

use App\Entity\WikiSite;

class WikiSiteCrudController extends CrudController {
	protected static $entityClass = WikiSite::class;
}
