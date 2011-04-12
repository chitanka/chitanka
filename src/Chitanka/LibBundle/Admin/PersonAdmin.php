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
		'role' => array(
			'type' => 'choice',
			'form_field_options' => array(
				'choices' => array(
					1 => 'Aвтор',
					2 => 'Преводач',
					3 => 'Автор и преводач',
				),
				'expanded' => true,
				'required' => true,
			),
		),
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
