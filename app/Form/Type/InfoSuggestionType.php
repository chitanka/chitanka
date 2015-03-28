<?php namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class InfoSuggestionType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('info', 'textarea')
			->add('name', 'text', [
				'required' => false,
			])
			->add('email', 'email', [
				'required' => false,
			])
			->add('save', 'submit');
	}

	public function getDefaultOptions(array $options) {
		return [
			'data_class' => 'App\Entity\InfoSuggestion',
		];
	}

	public function getName() {
		return 'suggest_info';
	}

}
