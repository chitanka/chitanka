<?php namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use App\Entity\User;

class UserAdmin extends Admin {
	protected $baseRoutePattern = 'user';
	protected $baseRouteName = 'admin_user';

	protected function configureShowField(ShowMapper $showMapper) {
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

	protected function configureListFields(ListMapper $listMapper) {
		$listMapper
			->addIdentifier('username')
			->add('email')
			->add('registration')
			->add('touched')
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
			->add('username')
			//->add('password')
			->add('realname')
			->add('email', null, ['required' => false])
			->add('allowemail')
			->add('groups', 'choice', [
				'required' => false,
				'choices' => array_combine(User::getGroupList(), User::getGroupList()),
				'multiple' => true,
				//'expanded' => true,
			])
			->add('news')
			//->add('opts')
			->add('token');
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid) {
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
