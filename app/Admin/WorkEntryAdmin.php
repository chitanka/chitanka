<?php namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class WorkEntryAdmin extends Admin {
	protected $baseRoutePattern = 'work-entry';
	protected $baseRouteName = 'admin_work_entry';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('type')
			->add('title')
			->add('author')
			->add('user')
			->add('comment')
			->add('status')
			->add('progress')
			->add('isFrozen')
			->add('tmpfiles')
			->add('tfsize')
			->add('uplfile')
			->add('adminStatus')
			->add('adminComment')
			->add('deletedAt')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->addIdentifier('title')
			->add('author')
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
			->add('type')
			->add('title')
			->add('author', null, ['required' => false])
			->add('user', 'sonata_type_model_list', ['required' => false])
			->add('comment', null, ['required' => false])
			->add('status')
			->add('progress')
			->add('isFrozen', null, ['required' => false])
			->add('tmpfiles', null, ['required' => false])
			->add('tfsize', null, ['required' => false])
			->add('uplfile', null, ['required' => false])
			->add('adminStatus')
			->add('adminComment')
			->add('deletedAt', null, ['required' => false]);
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('title')
			->add('author')
//			->add('user')
			->add('status')
			->add('progress')
			->add('isFrozen')
			->add('type')
			->add('adminStatus')
//			->add('date')
			->add('is_deleted', 'doctrine_orm_callback', [
				'callback' => function($queryBuilder, $alias, $field, $value) {
					if (!$value) {
						return;
					}

					$queryBuilder->andWhere("$alias.deletedAt IS NOT NULL");
				},
				'field_type' => 'checkbox'
			])
		;
	}

}
