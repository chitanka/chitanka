<?php namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class BookSiteAdmin extends Admin {
	protected $baseRoutePattern = 'book-site';
	protected $baseRouteName = 'admin_book_site';
	protected $translationDomain = 'admin';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('name')
			->add('url')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->addIdentifier('name')
			->add('url')
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
		$formMapper
			->with('General attributes')
				->add('name')
				->add('url')
				->setHelps(array(
					'url' => $this->trans('help.booksite.url')
				))
			->end()
		;

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('name')
			->add('url')
		;
	}

}
