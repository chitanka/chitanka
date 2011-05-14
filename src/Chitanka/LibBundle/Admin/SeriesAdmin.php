<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class SeriesAdmin extends Admin
{
	protected $baseRouteName = 'series';

	protected $list = array(
		'name' => array('identifier' => true),
		'slug',
		'_action' => array(
			'actions' => array(
				'delete' => array(),
				'edit' => array()
			)
		),
	);

	protected $form = array(
		'slug',
		'name',
		'orig_name',
		//'authors',// => array('form_field_options' => array('expanded' => true)),
	);

	protected $filter = array(
		'name',
		'orig_name',
	);
}
