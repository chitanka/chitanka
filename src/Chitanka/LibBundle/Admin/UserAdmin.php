<?php
namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Chitanka\LibBundle\Entity\User;

class UserAdmin extends Admin
{
	protected $baseRoutePattern = 'user';
	protected $baseRouteName = 'admin_user';
	protected $translationDomain = 'admin';

	protected function configureShowField(ShowMapper $showMapper)
	{
		$showMapper
			->add('username')
			//->add('password')
			->add('realname')
			->add('email')
			->add('allowemail')
			->add('groups')
			->add('news')
			->add('opts')
			->add('token')
		;
	}

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->addIdentifier('username')
			->add('email')
			->add('registration')
			->add('touched')
			->add('_action', 'actions', array(
				'actions' => array(
					'view' => array(),
					'edit' => array(),
					'delete' => array(),
				)
			))
		;
	}

	protected function configureFormFields(FormMapper $formMapper)
	{
		$formMapper
			->with('General attributes')
				->add('username')
				//->add('password')
				->add('realname')
				->add('email', null, array('required' => false))
				->add('allowemail')
				->add('groups', 'choice', array(
					'required' => false,
					'choices' => array_combine(User::getGroupList(), User::getGroupList()),
					'multiple' => true,
					//'expanded' => true,
				))
				->add('news')
				//->add('opts')
				->add('token')
			->end()
		;

	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('username')
			->add('email')
			->add('realname')
			->add('allowemail')
			->add('news')
			->add('registration')
			->add('touched')
		;
	}

}
