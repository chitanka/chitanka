<?php namespace App\Admin;

use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class BookAuthorAdmin extends Admin {
	protected $baseRoutePattern = 'book-author';
	protected $baseRouteName = 'admin_book_author';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('book')
			->add('person')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('book')
			->add('person')
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
			//->add('book')
			->add('person', 'sonata_type_model_list', ['required' => false])
			->end();
	}

}
