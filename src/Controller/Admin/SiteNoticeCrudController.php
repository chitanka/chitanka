<?php namespace App\Controller\Admin;

use App\Entity\SiteNotice;

class SiteNoticeCrudController extends CrudController {
	protected static $entityClass = SiteNotice::class;
}
