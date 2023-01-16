<?php namespace App\Controller\Admin;

use App\Entity\Sequence;

class SequenceCrudController extends CrudController {
	protected static $entityClass = Sequence::class;
}
