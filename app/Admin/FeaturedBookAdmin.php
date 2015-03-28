<?php namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class FeaturedBookAdmin extends Admin {
	protected $baseRoutePattern = 'featured-book';
	protected $baseRouteName = 'admin_featured_book';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('title')
			->add('author')
			->add('url')
			->add('cover')
			->add('description')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('cover', 'string', ['template' => 'App:FeaturedBookAdmin:list_cover.html.twig'])
			->addIdentifier('title')
			->add('author')
			->add('url', 'string', ['template' => 'App:FeaturedBookAdmin:list_url.html.twig'])
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
			->add('description');

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('title')
			->add('author')
			->add('url')
		;
	}

}
