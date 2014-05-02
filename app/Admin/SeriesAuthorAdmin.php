<?php namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class SeriesAuthorAdmin extends Admin {
	protected $baseRoutePattern = 'series-author';
	protected $baseRouteName = 'admin_series_author';
	protected $translationDomain = 'admin';

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
			//->add('series')
			->add('person', 'sonata_type_model_list', array('required' => false));
	}

}
