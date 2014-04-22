<?php
namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class TextAuthorAdmin extends Admin
{
	protected $baseRoutePattern = 'text-author';
	protected $baseRouteName = 'admin_text_author';
	protected $translationDomain = 'admin';

	protected function configureShowField(ShowMapper $showMapper)
	{
		$showMapper
			->add('person')
			->add('pos')
			->add('year')
		;
	}

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->add('text')
			->add('person')
			->add('year')
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
				//->add('text')
				->add('person', 'sonata_type_model_list', array('required' => false))
				->add('pos')
				->add('year')
			->end()
		;
	}

}
