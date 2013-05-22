<?php
namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class BookAuthorAdmin extends Admin
{
	protected $baseRouteName = 'admin_book_author';
	protected $translationDomain = 'admin';

	protected function configureShowField(ShowMapper $showMapper)
	{
		$showMapper
			->add('book')
			->add('person')
		;
	}

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->add('book')
			->add('person')
			->add('_action', 'actions', array(
				'actions' => array(
					'view' => array(),
					'edit' => array(),
					'delete' => array(),
				)
			))
		;
	}

	protected function configureFormFields(FormMapper $formMapper)
	{
		$formMapper
			->with('General attributes')
				//->add('book')
				->add('person', 'sonata_type_model_list', array('required' => false))
			->end()
		;
	}

}
