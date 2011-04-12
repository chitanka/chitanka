<?php

namespace Chitanka\LibBundle\Form;

use Symfony\Component\Form\HiddenField;
use Symfony\Component\Form\ChoiceField;
use Chitanka\LibBundle\Entity\TextRatingRepository;

class TextRatingForm extends EntityForm
{
	public function configure()
	{
		$this->addOption('em');

		$this->setDataClass('Chitanka\LibBundle\Entity\TextRating');

		$this->add(new HiddenField('text'));
		//$this->add(new HiddenField('user'));
		$this->add(new ChoiceField('rating', array(
			'choices' => TextRatingRepository::$ratings,
			'required' => false,
		)));
	}
}
