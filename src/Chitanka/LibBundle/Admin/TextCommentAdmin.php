<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class TextCommentAdmin extends Admin
{
	protected $baseRoutePattern = 'text-comment';
	protected $baseRouteName = 'admin_text_comment';

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->add('text')
			->add('rname')
			->add('time')
			->add('is_shown')
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
			->add('rname')
			->add('content')
			->add('is_shown')
		;

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('rname')
			->add('is_shown')
		;
	}

}
