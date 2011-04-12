<?php

namespace Chitanka\LibBundle\Form;

use Symfony\Component\Form\TextField;
use Symfony\Component\Form\ChoiceField;
use Symfony\Bundle\DoctrineBundle\Form\ValueTransformer\EntityToIDTransformer;

class LabelForm extends EntityForm
{
	protected $entityName = 'Label';

	public function setup()
	{
		$this->add(new TextField('slug'));
		$this->add(new TextField('name'));
		$this->add(new ChoiceField('parent', array(
			'choices'           => array('') + $this->getRepository()->getNames(),
			'value_transformer' => new EntityToIDTransformer(array(
				'em'        => $this->em,
				'className' => $this->getEntityName(),
			)),
		)));
	}

}
