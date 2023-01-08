<?php namespace App\Form\Type;

use App\Entity\TextRating;
use App\Persistence\TextRatingRepository;
use App\Service\Translation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextRatingType extends AbstractType {
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$translation = new Translation();
		$builder
			->add('text', HiddenType::class, [
				'data' => $options['data']->getText()->getId(),
				'mapped' => false,
			])
			//->add('user', HiddenType::class)
			->add('rating', ChoiceType::class, [
				'choices' => $translation->getRatingChoices(),
				'required' => false,
			]);
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults([
			'data_class' => TextRating::class,
		]);
	}

	public function getBlockPrefix() {
		return 'text_rating';
	}
}
