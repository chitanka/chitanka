<?php
namespace Chitanka\LibBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use FOS\CommentBundle\Form\CommentType as BaseCommentType;

class CommentType extends BaseCommentType
{

	/**
     * {@inheritDoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		parent::buildForm($builder, $options);
		$builder->add('cc', 'text', array(
			'required' => false,
			'label' => 'Копие до', // TODO move to translation
		));
	}

	public function getName()
	{
		return 'comment_form';
	}
}
