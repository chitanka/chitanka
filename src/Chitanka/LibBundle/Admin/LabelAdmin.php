<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class LabelAdmin extends Admin
{
	protected $baseRouteName = 'label';

	protected $list = array(
		'name' => array('identifier' => true),
		'slug',
		//'parent' => array('type' => 'many_to_one'),
		'_action' => array(
		'actions' => array(
			'delete' => array(),
			'edit' => array()
		)
		),
	);

	protected $form = array(
		'name',
		'slug',
		'parent' => array('form_field_options' => array('required' => false)),
	);

	protected $filter = array(
		'name',
	);
}
