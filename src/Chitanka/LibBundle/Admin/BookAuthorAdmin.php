<?php
namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class BookAuthorAdmin extends Admin
{
	protected $baseRouteName = 'admin_book_author';
	protected $translationDomain = 'admin';

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->add('book')
			->add('person')
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
			->add('person', 'sonata_type_model', array('required' => false), array('edit' => 'list'))
		;
	}

}
