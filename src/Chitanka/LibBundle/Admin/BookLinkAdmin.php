<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class BookLinkAdmin extends Admin
{
	protected $baseRoutePattern = 'book-link';
	protected $baseRouteName = 'admin_book_link';
	protected $translationDomain = 'admin';

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->add('book')
			->add('site')
			->addIdentifier('code')
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
			//->add('book')
			->add('site')
			->add('code')
		;
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('book')
			->add('site')
			->add('code')
		;
	}

}
