<?php namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class TextCommentType extends AbstractType {
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('text_id', HiddenType::class)
			->add('replyto_id', HiddenType::class)
			->add('content', TextareaType::class)
			->add('reader');
	}

	public function getDefaultOptions(array $options) {
		return [
			'data_class' => \App\Entity\TextComment::class,
		];
	}

	public function getBlockPrefix() {
		return 'text_comment';
	}
}
