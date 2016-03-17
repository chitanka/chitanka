<?php namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class EmailType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('subject', TextType::class)
			->add('message', TextareaType::class)
			->add('save', SubmitType::class);
	}

	public function getDefaultOptions(array $options) {
		return [
			'data_class' => \App\Entity\Email::class,
		];
	}

	public function getName() {
		return 'email_user';
	}

}
