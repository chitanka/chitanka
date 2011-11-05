<?php

namespace Chitanka\LibBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\AbstractType;

class TextCommentType extends AbstractType
{
	public function buildForm(FormBuilder $builder, array $options)
	{
		$builder
			->add('text_id', 'hidden')
			->add('replyto_id', 'hidden')
			->add('content', 'textarea')
			->add('reader');
	}

	public function getDefaultOptions(array $options)
	{
		return array(
			'data_class' => 'Chitanka\LibBundle\Entity\TextComment',
		);
	}

	public function getName()
	{
		return 'text_comment';
	}
}
