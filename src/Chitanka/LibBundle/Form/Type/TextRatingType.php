<?php

namespace Chitanka\LibBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\AbstractType;
use Chitanka\LibBundle\Entity\TextRatingRepository;

class TextRatingType extends AbstractType
{
	public function buildForm(FormBuilder $builder, array $options)
	{
		$builder
			->add('text', 'hidden')
			//->add('user', 'hidden')
			->add('rating', 'choice', array(
				'choices' => TextRatingRepository::$ratings,
				'required' => false,
			));
	}

	public function getDefaultOptions(array $options)
	{
		return array(
			'data_class' => 'Chitanka\LibBundle\Entity\TextRating',
		);
	}

	public function getName()
	{
		return 'text_rating';
	}
}
