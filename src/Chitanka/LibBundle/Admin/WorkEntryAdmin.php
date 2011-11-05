<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;

class WorkEntryAdmin extends Admin
{
	protected $baseRouteName = 'admin_work_entry';

	protected $list = array(
		'title' => array('identifier' => true),
		'author',
		'_action' => array(
			'actions' => array(
				'delete' => array(),
				'edit' => array()
			)
		),
	);

	protected $form = array(
		'type',
		'title',
		'author',
		//'user',
		'comment',
		'status',
		'progress',
		'is_frozen',
		'tmpfiles',
		'tfsize',
		'uplfile',
	);

	protected $filter = array(
		'title',
		'author',
	);
}
