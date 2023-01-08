<?php namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class RequestUsernameType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('email', EmailType::class, [
				'required' => false,
			])
			->add('save', SubmitType::class);
	}

	public function getBlockPrefix() {
		return 'request_username';
	}

}
