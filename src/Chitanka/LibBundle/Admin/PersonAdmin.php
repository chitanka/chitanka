<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Chitanka\LibBundle\Entity\Person;

class PersonAdmin extends Admin
{
	protected $baseRouteName = 'admin_person';

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->addIdentifier('name')
			->add('slug')
			->add('orig_name')

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
			->add('slug', null, array('required' => false))
			->add('name')
			->add('orig_name', null, array('required' => false))
			->add('real_name', null, array('required' => false))
			->add('oreal_name', null, array('required' => false))
			->add('country')
			->add('is_author', null, array('required' => false))
			->add('is_translator', null, array('required' => false))
			->add('info', null, array('required' => false))
			->add('type', 'choice', array(
				'choices' => Person::getTypeList(),
				//'expanded' => true,
				'required' => false,
			))
			->add('person', 'sonata_type_model', array('required' => false), array('edit' => 'list'))
		;

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('name')
			->add('orig_name')
			->add('country')
		;
	}

}
