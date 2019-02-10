<?php namespace App\Form\Type;

use App\Service\Translation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class RandomTextFilter extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {
		$typeChoices = (new Translation())->getTextTypeChoices();
		$builder
			->add('type', ChoiceType::class, [
				'choices' => $typeChoices,
				'multiple' => true,
				'expanded' => true,
			])
			->add('submit', SubmitType::class);
	}

}
