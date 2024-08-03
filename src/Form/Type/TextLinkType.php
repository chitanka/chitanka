<?php namespace App\Form\Type;

use App\Entity\ExternalSite;
use App\Entity\TextLink;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextLinkType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add('site', null, ['query_builder' => function($repo) {
			return $repo->createQueryBuilder('e')->orderBy('e.name');
		}]);
		$builder->add('code');
		$builder->add('description');
		$builder->add('mediaType', ChoiceType::class, [
			'choices' => array_combine(ExternalSite::MEDIA_TYPES, ExternalSite::MEDIA_TYPES),
			'choice_translation_domain' => false,
		]);
	}

	public function getBlockPrefix() {
		return 'text_link';
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => TextLink::class,
		));
	}
}
