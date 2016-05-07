<?php namespace App\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class PublisherAdmin extends Admin {
	protected $baseRoutePattern = 'publisher';
	protected $baseRouteName = 'admin_publisher';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('slug')
			->add('name')
			->add('email')
			->add('extraInfo')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('slug')
			->addIdentifier('name')
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
			->add('slug')
			->add('name')
			->add('website')
			->add('email')
			->add('extraInfo', null, ['attr' => ['class' => 'richhtml']])
			->end();
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('slug')
			->add('name')
		;
	}

}
