<?php

namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class QuestionAdmin extends Admin
{
	protected $baseRoutePattern = 'question';
	protected $baseRouteName = 'admin_question';

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->addIdentifier('question')
			->add('answers')
			->add('_action', 'actions', array(
				'actions' => array(
					'delete' => array(),
					'edit' => array(),
				)
			))
		;
	}

	protected function configureFormFields(FormMapper $formMapper)
	{
		$formMapper
			->add('question')
			->add('answers')
			->setHelps(array(
				'answers' => $this->trans('admin.help.question.answers'),
			))
		;

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('question')
			->add('answers')
		;
	}
}
