<?php namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
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
			->add('code')
			->add('addition')
		;
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('book')
			->add('code')
		;
	}

}
