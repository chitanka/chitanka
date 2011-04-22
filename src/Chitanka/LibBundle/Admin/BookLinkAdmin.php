<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;

class BookLinkAdmin extends Admin
{
	protected $baseRouteName = 'book_link';

	protected $list = array(
		'book',
		'site',
		'code' => array('identifier' => true),
		'_action' => array(
			'actions' => array(
				'delete' => array(),
				'edit' => array()
			)
		),
	);

	protected $form = array(
		'book',
		'site',
		'code',
	);
}
