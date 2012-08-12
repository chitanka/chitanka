<?php
namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class UserTextContribAdmin extends Admin
{
	protected $baseRoutePattern = 'user-text-contrib';
	protected $baseRouteName = 'admin_user_text_contrib';
	protected $translationDomain = 'admin';

	protected function configureShowField(ShowMapper $showMapper)
	{
		$showMapper
			->add('user')
			->add('username')
			->add('text')
			->add('size')
			->add('percent')
			->add('comment')
			->add('date')
			->add('humandate')
		;
	}

	protected function configureFormFields(FormMapper $formMapper)
	{
		$formMapper
			->add('user', 'sonata_type_model_list')
			->add('username')
			->add('text', 'sonata_type_model_list')
			->add('size')
			->add('percent')
			->add('comment')
			->add('date')
			->add('humandate')
		;

	}

}
