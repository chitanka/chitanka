<?php namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class SequenceAdmin extends Admin {
	protected $baseRoutePattern = 'sequence';
	protected $baseRouteName = 'admin_sequence';

	public $extraActions = 'App:SequenceAdmin:extra_actions.html.twig';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('name')
			->add('slug')
			->add('publisher')
			->add('isSeqnrVisible')
			->add('books')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->addIdentifier('name')
			->add('slug')
			->add('_action', 'actions', array(
				'actions' => array(
					'view' => array(),
					'edit' => array(),
					'delete' => array(),
				)
			))
		;
	}

	protected function configureFormFields(FormMapper $formMapper) {
		$formMapper->with('General attributes');
		$formMapper
			->add('name')
			->add('slug')
			->add('publisher', null, array('required' => false))
			->add('isSeqnrVisible', null, array('required' => false));
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('slug')
			->add('name')
			->add('publisher')
			->add('isSeqnrVisible')
		;
	}

}
