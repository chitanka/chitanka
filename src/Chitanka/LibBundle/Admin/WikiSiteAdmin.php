<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class WikiSiteAdmin extends Admin
{
	protected $baseRouteName = 'wiki_site';

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
		'code',
		'name',
		'url',
		'intro',
	);
}
