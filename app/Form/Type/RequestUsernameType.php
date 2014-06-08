<?php namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RequestUsernameType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('email', 'text', array(
				'required' => false,
			))
			->add('save', 'submit');
	}

	public function getName() {
		return 'request_username';
	}

}
