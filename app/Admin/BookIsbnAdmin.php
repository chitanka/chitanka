<?php namespace App\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class BookIsbnAdmin extends Admin {
	protected $baseRoutePattern = 'book-isbn';
	protected $baseRouteName = 'admin_book_isbn';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('book')
			->add('code')
			->add('addition')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('book')
			->addIdentifier('code')
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
			->add('code')
			->add('addition')
			->end();
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('book')
			->add('code')
		;
	}

}
