<?php

namespace Chitanka\LibBundle\Form;

use Symfony\Component\Form\HiddenField;
use Symfony\Component\Form\EntityChoiceField;

class TextLabelForm extends EntityForm
{
	public function configure()
	{
		$this->addOption('em');

		$this->setDataClass('Chitanka\LibBundle\Entity\TextLabel');

		$this->add(new HiddenField('text'));
		$this->add(new EntityChoiceField('label', array(
			'em' => $this->getOption('em'),
			'class' => 'Chitanka\LibBundle\Entity\Label',
			'query_builder' => function ($repository) {
				return $repository->createQueryBuilder('l')->orderBy('l.name');
			}
		)));
	}

	public function process()
	{
		$text = $this->getData()->getText();
		$text->addLabel($this->getData()->getLabel());
		$em = $this->getOption('em');
		$em->persist($text);
		$em->flush();
	}
}
