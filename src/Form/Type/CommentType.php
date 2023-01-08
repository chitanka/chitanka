<?php namespace App\Form\Type;

use FOS\CommentBundle\Form\CommentType as BaseCommentType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class CommentType extends BaseCommentType {

	/**
     * {@inheritDoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
		$builder->add('cc', TextType::class, [
			'required' => false,
			'label' => 'Уведомяване на', // TODO move to translation
			'attr' => [
				'title' => 'Няколко имена се разделят със запетаи, напр. „Иванчо, Марийка, Гошко“',
			]
		]);
	}

	public function getBlockPrefix() {
		return 'comment_form';
	}
}
