<?php namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextLabelType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('text', HiddenType::class, [
				'data' => $options['data']->getText()->getId(),
				'mapped' => false,
			])
			->add('label', EntityType::class, [
				'class' => 'App:Label',
				'query_builder' => function (EntityRepository $repo) use ($options) {
					return $repo->createQueryBuilder('l')
						->where('l.group = ?1')->setParameter(1, $options['group'])
						->orderBy('l.name');
				}
			]);
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults([
			'data_class' => \App\Entity\TextLabel::class,
			'group' => null,
		]);
	}

	public function getBlockPrefix() {
		return 'text_label';
	}

}
