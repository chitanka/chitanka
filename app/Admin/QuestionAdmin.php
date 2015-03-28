<?php namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class QuestionAdmin extends Admin {
	protected $baseRoutePattern = 'question';
	protected $baseRouteName = 'admin_question';

	protected function configureShowField(ShowMapper $showMapper) {
		$showMapper
			->add('question')
			->add('answers')
		;
	}

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->addIdentifier('question')
			->add('answers')
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
			->add('question')
			->add('answers')
			->setHelps([
				'answers' => $this->trans('help.question.answers'),
			]);

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
		$datagrid
			->add('question')
			->add('answers')
		;
	}
}
