<?php namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use App\Entity\TextRatingRepository;

class TextRatingType extends AbstractType {
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('text', 'hidden', [
				'data' => $options['data']->getText()->getId(),
				'mapped' => false,
			])
			//->add('user', 'hidden')
			->add('rating', 'choice', [
				'choices' => TextRatingRepository::$ratings,
				'required' => false,
			]);
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults([
			'data_class' => 'App\Entity\TextRating',
		]);
	}

	public function getName() {
		return 'text_rating';
	}
}
