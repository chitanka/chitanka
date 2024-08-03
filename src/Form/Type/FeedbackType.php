<?php namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class FeedbackType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('referer', HiddenType::class)
			->add('comment', TextareaType::class)
			->add('subject')
			->add('name', TextType::class, [
				'required' => false,
			])
			->add('email', EmailType::class, [
				'required' => false,
			]);
	}

	public function getDefaultOptions(array $options) {
		return [
			'data_class' => \App\Entity\Feedback::class,
		];
	}

	public function getBlockPrefix() {
		return 'feedback';
	}

}
