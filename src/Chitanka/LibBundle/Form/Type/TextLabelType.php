<?php

namespace Chitanka\LibBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\AbstractType;

class TextLabelType extends AbstractType
{
	public function buildForm(FormBuilder $builder, array $options)
	{
		$builder
			->add('text', 'hidden')
			->add('label', 'entity', array(
				'class' => 'LibBundle:Label',
				'query_builder' => function ($repo) {
					return $repo->createQueryBuilder('l')->orderBy('l.name');
				}
			));
	}

	public function getDefaultOptions(array $options)
	{
		return array(
			'data_class' => 'Chitanka\LibBundle\Entity\TextLabel',
		);
	}

	public function getName()
	{
		return 'text_label';
	}

}
