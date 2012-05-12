<?php
namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class LicenseAdmin extends Admin
{
	protected $baseRoutePattern = 'license';
	protected $baseRouteName = 'admin_license';
	protected $translationDomain = 'admin';

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->addIdentifier('code')
			->add('name')
			->add('free')
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
			->add('code')
			->add('name')
			->add('fullname')
			->add('free')
			->add('copyright')
			->add('uri')
		;

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('name')
			->add('fullname')
		;
	}

}
