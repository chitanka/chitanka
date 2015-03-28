<?php namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LabelType extends AbstractType {
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('slug')
			->add('name')
			->add('parent', 'entity', [
				'class' => 'App:Label',
				'query_builder' => function ($repo) {
					return $repo->createQueryBuilder('l')->orderBy('l.name');
				}
			]);
	}

	public function getDefaultOptions(array $options) {
		return [
			'data_class' => 'App\Entity\Label',
		];
	}

	public function getName() {
		return 'label';
	}

}
