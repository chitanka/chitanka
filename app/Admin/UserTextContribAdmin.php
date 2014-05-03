<?php namespace App\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class UserTextContribAdmin extends Admin {

	protected $baseRoutePattern = 'user-text-contrib';
	protected $baseRouteName = 'admin_user_text_contrib';

	protected function configureShowField(ShowMapper $showMapper) {
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

	protected function configureFormFields(FormMapper $formMapper) {
		$formMapper->with('General attributes');
		$formMapper
			->add('comment')
			->add('user', 'sonata_type_model_list')
			->add('username')
			->add('percent')
			->add('humandate')
			->add('date');
	}

}
