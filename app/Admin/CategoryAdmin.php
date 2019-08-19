<?php namespace App\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class CategoryAdmin extends Admin {
	protected $baseRoutePattern = 'category';
	protected $baseRouteName = 'admin_category';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('name')
			->add('slug')
			->add('description')
			->add('parent')
			->add('children')
			->add('nrOfBooks')
			->add('books')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->addIdentifier('name')
			->add('slug')
			->add('nrOfBooks')
			->add('parent')
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
			->add('name')
			->add('slug')
			->add('description')
			->add('parent', null, ['required' => false, 'query_builder' => function ($repo) {
				return $repo->createQueryBuilder('e')->orderBy('e.name');
			}])
			->end();

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('slug')
			->add('name')
		;
	}

}
