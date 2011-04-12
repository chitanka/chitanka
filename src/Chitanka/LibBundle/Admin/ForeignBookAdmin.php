<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;

class ForeignBookAdmin extends Admin
{
	protected $baseRouteName = 'foreign_book';

	protected $list = array(
		'cover' => array('template' => 'LibBundle:FeaturedBookAdmin:list_cover.html.twig'),
		'title' => array('identifier' => true),
		'author',
		'url' => array('template' => 'LibBundle:FeaturedBookAdmin:list_url.html.twig'),
		'_action' => array(
			'actions' => array(
				'delete' => array(),
				'edit' => array()
			)
		),
	);

	protected $form = array(
		'title',
		'author',
		'url',
		'cover',
	);

	protected $filter = array(
		'title',
		'author',
		'url',
	);
}
