<?php namespace App\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class BookLinkAdmin extends Admin {
	protected $baseRoutePattern = 'book-link';
	protected $baseRouteName = 'admin_book_link';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('book')
			->add('site')
			->add('code')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('book')
			->add('site')
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
			//->add('book')
			->add('site')
			->add('code')
			->end();
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('book')
			->add('site')
			->add('code')
		;
	}

}
