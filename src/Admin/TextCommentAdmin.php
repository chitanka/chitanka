<?php namespace App\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;

class TextCommentAdmin extends Admin {
	protected $baseRoutePattern = 'text-comment';
	protected $baseRouteName = 'admin_text_comment';

	protected function configureRoutes(RouteCollection $collection) {
		$collection->remove('create');
	}

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('text')
			->add('user')
			->add('rname')
			->add('time')
			->add('content')
			->add('is_shown')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('text')
			->add('rname')
			->add('time')
			->add('is_shown')
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
			->add('rname')
			->add('content')
			->add('is_shown', null, ['required' => false])
			->end();
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('rname')
			->add('is_shown')
		;
	}

}
