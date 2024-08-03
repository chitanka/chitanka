<?php namespace App\Form\Type;

use App\Entity\UserTextContrib;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserTextContribType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add('comment');
		// TODO Include user relation when autocomplete is available
		$builder->add('username');
		$builder->add('percent');
		$builder->add('humandate');
		$builder->add('date');
	}

	public function getBlockPrefix() {
		return 'user_contrib';
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults(array(
			'data_class' => UserTextContrib::class,
		));
	}
}
