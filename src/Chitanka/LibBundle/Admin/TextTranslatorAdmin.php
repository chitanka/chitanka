<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class TextTranslatorAdmin extends Admin
{
	protected $baseRouteName = 'admin_text_translator';
	protected $translationDomain = 'admin';

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->add('text')
			->add('person')
			->add('year')
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
			//->add('text')
			->add('person', 'sonata_type_model', array('required' => false), array('edit' => 'list'))
			->add('pos')
			->add('year')
		;
	}

}
