<?php namespace App\Form\Type;

use App\Entity\BookLink;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookLinkType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add('site');
		$builder->add('code');
	}

	public function getBlockPrefix() {
		return 'book_link';
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => BookLink::class,
		));
	}
}
