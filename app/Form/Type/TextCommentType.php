<?php namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TextCommentType extends AbstractType {
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('text_id', 'hidden')
			->add('replyto_id', 'hidden')
			->add('content', 'textarea')
			->add('reader');
	}

	public function getDefaultOptions(array $options) {
		return [
			'data_class' => 'App\Entity\TextComment',
		];
	}

	public function getName() {
		return 'text_comment';
	}
}
