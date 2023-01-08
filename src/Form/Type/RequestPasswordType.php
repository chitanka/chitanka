<?php namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class RequestPasswordType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('username', TextType::class, [
				'required' => false,
			])
			->add('save', SubmitType::class);
	}

	public function getBlockPrefix() {
		return 'request_password';
	}

}
