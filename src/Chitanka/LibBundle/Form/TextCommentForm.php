<?php

namespace Chitanka\LibBundle\Form;

use Symfony\Component\Form\HiddenField;
use Symfony\Component\Form\TextareaField;
use Chitanka\LibBundle\Entity\TextRatingRepository;

class TextCommentForm extends EntityForm
{
	public function configure()
	{
		$this->addOption('em');

		$this->setDataClass('Chitanka\LibBundle\Entity\TextComment');

		$this->add(new HiddenField('text_id'));
		$this->add(new HiddenField('replyto_id'));
		$this->add(new TextareaField('content'));
		$this->add(new TextareaField('reader'));
	}
}
