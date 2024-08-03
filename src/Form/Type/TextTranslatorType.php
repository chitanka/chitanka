<?php namespace App\Form\Type;

use App\Entity\TextTranslator;
use App\Persistence\PersonRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextTranslatorType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add('person', null, [
			'query_builder' => function (PersonRepository $repo) {
				return $repo->asTranslator()->getBaseQueryBuilder('e')->orderBy('e.name');
			}
		]);
		$builder->add('pos');
		$builder->add('year');
	}

	public function getBlockPrefix() {
		return 'text_translator';
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => TextTranslator::class,
		));
	}
}
