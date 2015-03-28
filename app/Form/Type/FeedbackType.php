<?php namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class FeedbackType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('referer', 'hidden')
			->add('comment', 'textarea')
			->add('subject')
			->add('name', 'text', [
				'required' => false,
			])
			->add('email', 'email', [
				'required' => false,
			]);
	}

	public function getDefaultOptions(array $options) {
		return [
			'data_class' => 'App\Entity\Feedback',
		];
	}

	public function getName() {
		return 'feedback';
	}

}
