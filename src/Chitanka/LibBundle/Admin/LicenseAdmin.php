<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;

class LicenseAdmin extends Admin
{
	protected $baseRouteName = 'license';

	protected $list = array(
		'code' => array('identifier' => true),
		'name',
		'free',
		'_action' => array(
			'actions' => array(
				'delete' => array(),
				'edit' => array()
			)
		),
	);

	protected $form = array(
		'code',
		'name',
		'fullname',
		'free',
		'copyright',
		'uri',
	);

	protected $filter = array(
		'name',
		'fullname',
	);
}
