<?php

namespace Chitanka\LibBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FieldError;
use Chitanka\LibBundle\Util\String;

class FeedbackType extends AbstractType
{

	public function buildForm(FormBuilder $builder, array $options)
	{
		$builder
			->add('referer', 'hidden')
			->add('comment', 'textarea')
			->add('subject')
			->add('name', 'text', array(
				'required' => false,
			))
			->add('email', 'email', array(
				'required' => false,
			));
	}

	public function getDefaultOptions(array $options)
	{
		return array(
			'data_class' => 'Chitanka\LibBundle\Entity\Feedback',
		);
	}

	public function getName()
	{
		return 'feedback';
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
