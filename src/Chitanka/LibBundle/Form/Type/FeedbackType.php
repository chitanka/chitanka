<?php
namespace Chitanka\LibBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Chitanka\LibBundle\Util\String;

class FeedbackType extends AbstractType
{

	public function buildForm(FormBuilderInterface $builder, array $options)
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

}
