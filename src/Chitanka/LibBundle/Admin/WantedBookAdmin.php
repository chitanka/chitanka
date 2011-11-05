<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class WantedBookAdmin extends Admin
{
	protected $baseRouteName = 'admin_wanted_book';

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->addIdentifier('name')
			->add('_action', 'actions', array(
				'actions' => array(
					'delete' => array(),
					'edit' => array(),
				)
			))
		;
	}

	protected function configureFormFields(FormMapper $formMapper)
	{
		$formMapper
			->add('name')
			->add('description')
		;

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('name')
		;
	}

}
