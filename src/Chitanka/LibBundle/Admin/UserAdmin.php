<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;

class UserAdmin extends Admin
{
	protected $baseRoutePattern = 'user';
	protected $baseRouteName = 'admin_user';

	protected $list = array(
		'username' => array('identifier' => true),
		'email',
		'_action' => array(
			'actions' => array(
				'delete' => array(),
				'edit' => array()
			)
		),
	);

	protected $form = array(
		'username',
		'password',
		'realname',
		'email',
		'allowemail',
		//'groups',
		'news',
		//'opts',
	);

	protected $filter = array(
		'username',
		'email',
	);
}
