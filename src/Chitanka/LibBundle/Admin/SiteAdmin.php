<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;

class SiteAdmin extends Admin
{
	protected $baseRouteName = 'site';

	protected $list = array(
		'name' => array('identifier' => true),
		'url',
		'_action' => array(
			'actions' => array(
				'delete' => array(),
				'edit' => array()
			)
		),
	);

	protected $form = array(
		'name',
		'url',
		'description',
	);

	protected $filter = array(
		'name',
		'url',
		'description',
	);
}
