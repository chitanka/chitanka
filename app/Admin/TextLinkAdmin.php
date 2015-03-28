<?php namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class TextLinkAdmin extends Admin {
	protected $baseRoutePattern = 'text-link';
	protected $baseRouteName = 'admin_text_link';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('text')
			->add('site')
			->add('code')
			->add('description')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('text')
			->add('site')
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
			//->add('text')
			->add('site')
			->add('code')
			->add('description');
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('text')
			->add('site')
			->add('code')
		;
	}

}
