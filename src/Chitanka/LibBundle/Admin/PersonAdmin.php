<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class PersonAdmin extends Admin
{
	protected $baseRouteName = 'person';

	protected $list = array(
		'name' => array('identifier' => true),
		'slug',
		'orig_name',
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
		'real_name',
		'oreal_name',
		'country',
		'is_author',
		'is_translator',
		'info',
		'type' => array(
			'type' => 'choice',
			'form_field_options' => array(
				'choices' => array(
					'p' => 'Псевдоним',
					'r' => 'Истинско име',
					'a' => 'Алтернативно изписване',
				),
				'expanded' => true,
			),
		),
		'person' => array('form_field_options' => array('required' => false)),
	);

	protected $filter = array(
		'name',
		'orig_name',
		'country',
	);
}
