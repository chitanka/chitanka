<?php namespace App\Form\Type;

use App\Entity\BookIsbn;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookIsbnType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add('code');
		$builder->add('addition');
	}

	public function getBlockPrefix() {
		return 'book_isbn';
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => BookIsbn::class,
		));
	}
}
