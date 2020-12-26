<?php namespace App\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
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
			->add('type')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->add('text')
			->add('site')
			->addIdentifier('code')
			->add('type')
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
			//->add('text')
			->add('site')
			->add('code')
			->add('description')
			->add('type', 'choice', [
				'required' => false,
				'choices' => array_combine($types = ['audio', 'youtube'], $types),
			])
			->end();
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('site')
			->add('code')
			->add('type')
		;
	}

}
