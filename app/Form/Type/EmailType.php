<?php namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EmailType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('subject', 'text')
			->add('message', 'textarea')
			->add('save', 'submit');
	}

	public function getDefaultOptions(array $options) {
		return [
			'data_class' => 'App\Entity\Email',
		];
	}

	public function getName() {
		return 'email_user';
	}

}
