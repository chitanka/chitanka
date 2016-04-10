<?php namespace App\Admin;

use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class TextTranslatorAdmin extends Admin {
	protected $baseRoutePattern = 'text-translator';
	protected $baseRouteName = 'admin_text_translator';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('person')
			->add('pos')
			->add('year')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('text')
			->add('person')
			->add('year')
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
			//->add('text')
			->add('person', 'sonata_type_model_list', ['required' => false])
			->add('pos')
			->add('year')
			->end();
	}

}
