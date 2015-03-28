<?php namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class ForeignBookAdmin extends Admin {
	protected $baseRoutePattern = 'foreign-book';
	protected $baseRouteName = 'admin_foreign_book';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('title')
			->add('author')
			->add('url')
			->add('cover')
			->add('description')
			->add('isFree')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('cover', 'string', ['template' => 'App:FeaturedBookAdmin:list_cover.html.twig'])
			->addIdentifier('title')
			->add('author')
			->add('url', 'string', ['template' => 'App:FeaturedBookAdmin:list_url.html.twig'])
			->add('isFree')
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
			->add('title')
			->add('author')
			->add('url')
			->add('cover')
			->add('description')
			->add('isFree');

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('title')
			->add('author')
			->add('url')
			->add('isFree')
		;
	}

}
