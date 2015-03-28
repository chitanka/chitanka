<?php namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class SeriesAuthorAdmin extends Admin {
	protected $baseRoutePattern = 'series-author';
	protected $baseRouteName = 'admin_series_author';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('series')
			->add('person')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('series')
			->add('person')
			->add('_action', 'actions', [
				'actions' => [
					'view' => [],
					'edit' => [],
					'delete' => [],
				]
			])
		;
	}

	protected function configureFormFields(FormMapper $formMapper) {
		$formMapper->with('General attributes');
		$formMapper
			//->add('series')
			->add('person', 'sonata_type_model_list', ['required' => false]);
	}

}
