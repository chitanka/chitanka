<?php

namespace Chitanka\LibBundle\Form;

use Symfony\Component\Form\HiddenField;
use Symfony\Component\Form\TextareaField;
use Symfony\Component\Form\TextField;
use Symfony\Component\Form\FieldError;
use Chitanka\LibBundle\Util\String;

class FeedbackForm extends EntityForm
{
	public function configure()
	{
		$this->setDataClass('Chitanka\LibBundle\Entity\Feedback');

		$this->add(new HiddenField('referer'));
		$this->add(new TextareaField('comment'));
		$this->add(new TextField('subject'));
		$this->add(new TextField('name', array(
			'required' => false,
		)));
		$this->add(new TextField('email', array(
			'required' => false,
		)));
	}

	public function isValid()
	{
		if ( ! parent::isValid()) {
			return false;
		}

		// TODO refactor as ... a constraint?
		if (String::isSpam($this->get('comment')->getData())) {
			$this->addError(new FieldError('Коментарът ви е определен като спам. Вероятно съдържа прекалено много уеб адреси.'));

			return false;
		}

		return true;
	}

}
