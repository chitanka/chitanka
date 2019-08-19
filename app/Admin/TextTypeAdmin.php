<?php namespace App\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class TextTypeAdmin extends Admin {
	protected $baseRoutePattern = 'text-type';
	protected $baseRouteName = 'admin_text_type';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('code')
			->add('name')
			->add('description')
			->add('nrOfTexts')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->addIdentifier('code')
			->add('name')
			->add('nrOfTexts')
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
			->add('code', null, [
				'disabled' => true,
			])
			->add('name')
			->add('description')
			->end();
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('code')
			->add('name')
		;
	}

}
