<?php namespace App\Form\Type;

use App\Entity\TextAuthor;
use App\Persistence\PersonRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextAuthorType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add('person', null, [
			'query_builder' => function (PersonRepository $repo) {
				return $repo->asAuthor()->getBaseQueryBuilder('e')->orderBy('e.name');
			}
		]);
		$builder->add('pos');
		$builder->add('year');
	}

	public function getBlockPrefix() {
		return 'text_author';
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => TextAuthor::class,
		));
	}
}
