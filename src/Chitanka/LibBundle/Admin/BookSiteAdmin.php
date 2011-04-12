<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;

class BookSiteAdmin extends Admin
{
	protected $baseRouteName = 'book_site';

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
	);
}
