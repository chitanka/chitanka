<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class WorkEntryAdmin extends Admin
{
	protected $baseRoutePattern = 'work-entry';
	protected $baseRouteName = 'admin_work_entry';
	protected $translationDomain = 'admin';

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->addIdentifier('title')
			->add('author')
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
			->add('type')
			->add('title')
			->add('author', null, array('required' => false))
			->add('user', 'sonata_type_model', array('required' => false), array('edit' => 'list'))
			->add('comment', null, array('required' => false))
			->add('status')
			->add('progress')
			->add('is_frozen')
			->add('tmpfiles', null, array('required' => false))
			->add('tfsize', null, array('required' => false))
			->add('uplfile', null, array('required' => false))
			->add('deleted_at', null, array('required' => false))
		;

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('title')
			->add('author')
//			->add('user')
			->add('status')
			->add('progress')
			->add('is_frozen')
			->add('type')
//			->add('date')
			->add('is_deleted', 'doctrine_orm_callback', array(
				'callback' => function($queryBuilder, $alias, $field, $value) {
					if (!$value) {
						return;
					}

					$queryBuilder->andWhere("$alias.deleted_at IS NOT NULL");
				},
				'field_type' => 'checkbox'
			))
		;
	}

}
