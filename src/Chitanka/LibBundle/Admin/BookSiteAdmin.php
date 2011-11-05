<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class BookSiteAdmin extends Admin
{
	protected $baseRouteName = 'admin_book_site';

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->addIdentifier('name')
			->add('url')
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
			->add('url')
			->setHelps(array(
				'url' => $this->trans('admin.help.booksite.url')
			))
		;

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('name')
			->add('url')
		;
	}

}
