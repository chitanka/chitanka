<?php namespace App\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class SiteNoticeAdmin extends Admin {

	protected $baseRoutePattern = 'site-notice';
	protected $baseRouteName = 'admin_site_notice';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('name')
			->add('content')
			->add('isActive')
			->add('isForFrontPage')
			->add('style')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->addIdentifier('name')
			->add('isActive')
			->add('isForFrontPage')
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
			->add('content')
			->add('isActive', null, ['required' => false])
			->add('isForFrontPage', null, ['required' => false])
			->add('style')
			->end();
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('name')
			->add('content')
			->add('isActive')
			->add('isForFrontPage')
		;
	}

}
