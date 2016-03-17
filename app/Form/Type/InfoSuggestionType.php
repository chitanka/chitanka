<?php namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class InfoSuggestionType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('info', TextareaType::class)
			->add('name', TextType::class, [
				'required' => false,
			])
			->add('email', EmailType::class, [
				'required' => false,
			])
			->add('save', SubmitType::class);
	}

	public function getDefaultOptions(array $options) {
		return [
			'data_class' => \App\Entity\InfoSuggestion::class,
		];
	}

	public function getName() {
		return 'suggest_info';
	}

}
