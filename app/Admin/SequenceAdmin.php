<?php namespace App\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
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
			->add('_action', 'actions', [
				'actions' => [
					'show' => [],
					'edit' => [],
					'delete' => [],
				]
			])
		;
	}

	protected function configureFormFields(FormMapper $formMapper) {
		$formMapper->with('General attributes')
			->add('name')
			->add('slug')
			->add('publisher', null, ['required' => false])
			->add('isSeqnrVisible', null, ['required' => false])
			->add('annotation', null, ['attr' => ['class' => 'richhtml']])
			->end();
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
