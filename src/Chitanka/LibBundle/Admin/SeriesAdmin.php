<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class SeriesAdmin extends Admin
{
	protected $baseRouteName = 'admin_series';

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->addIdentifier('name')
			->add('slug')
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
			->add('slug')
			->add('name')
			->add('orig_name', null, array('required' => false))
		;

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('name')
			->add('orig_name')
		;
	}

}
