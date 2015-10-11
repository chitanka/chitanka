<?php namespace App\Admin;

use App\Entity\Label;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class LabelAdmin extends Admin {
	protected $baseRoutePattern = 'label';
	protected $baseRouteName = 'admin_label';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('name')
			->add('slug')
			->add('group')
			->add('description')
			->add('parent')
			->add('nrOfTexts')
			->add('children')
			->add('texts')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->addIdentifier('name')
			->add('slug')
			->add('group')
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
			->add('name')
			->add('slug')
			->add('group', 'choice', ['choices' => array_combine(Label::getAvailableGroups(), Label::getAvailableGroups())])
			->add('description')
			->add('parent', null, ['required' => false, 'query_builder' => function ($repo) {
				return $repo->createQueryBuilder('e')->orderBy('e.name');
			}]);

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('slug')
			->add('name')
			->add('group')
		;
	}

}
