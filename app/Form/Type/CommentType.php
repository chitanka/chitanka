<?php namespace App\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use FOS\CommentBundle\Form\CommentType as BaseCommentType;

class CommentType extends BaseCommentType {

	/**
     * {@inheritDoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder->add('cc', 'text', [
			'required' => false,
			'label' => 'Уведомяване на', // TODO move to translation
			'attr' => [
				'title' => 'Няколко имена се разделят със запетаи, напр. „Иванчо, Марийка, Гошко“',
			]
		]);
	}

	public function getName() {
		return 'comment_form';
	}
}
