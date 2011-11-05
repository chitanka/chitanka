<?php

namespace Chitanka\LibBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Bundle\DoctrineBundle\Form\ValueTransformer\EntityToIDTransformer;

class LabelType extends AbstractType
{
	public function buildForm(FormBuilder $builder, array $options)
	{
		$builder
			->add('slug')
			->add('name')
			->add('parent', 'entity', array(
				'class' => 'LibBundle:Label',
				'query_builder' => function ($repo) {
					return $repo->createQueryBuilder('l')->orderBy('l.name');
				}
			));
	}

	public function getDefaultOptions(array $options)
	{
		return array(
			'data_class' => 'Chitanka\LibBundle\Entity\Label',
		);
	}

	public function getName()
	{
		return 'label';
	}

}
