<?php namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RequestPasswordType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('username', 'text', [
				'required' => false,
			])
			->add('save', 'submit');
	}

	public function getName() {
		return 'request_password';
	}

}
