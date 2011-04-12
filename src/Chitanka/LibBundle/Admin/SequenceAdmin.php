<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class SequenceAdmin extends Admin
{
	protected $baseRouteName = 'sequence';

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
		'name',
		'slug',
		'publisher',
	);

	protected $filter = array(
		'name',
		'publisher',
	);
}
