<?php namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;

class TextLabelType extends AbstractType {

	private $group;

	public function __construct($group) {
		$this->group = $group;
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('text', 'hidden', [
				'data' => $options['data']->getText()->getId(),
				'mapped' => false,
			])
			->add('label', 'entity', [
				'class' => 'App:Label',
				'query_builder' => function (EntityRepository $repo) {
					return $repo->createQueryBuilder('l')
						->where('l.group = ?1')->setParameter(1, $this->group)
						->orderBy('l.name');
				}
			]);
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults([
			'data_class' => 'App\Entity\TextLabel',
		]);
	}

	public function getName() {
		return 'text_label';
	}

}
