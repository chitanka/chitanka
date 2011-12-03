<?php
namespace Chitanka\LibBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NotSpamValidator extends ConstraintValidator
{
	public function isValid($value, Constraint $constraint)
	{
		$isSpam = false;
		$isSpam = $isSpam || strpos($value, 'href=') !== false;
		$isSpam = $isSpam || strpos($value, 'url=') !== false;
		$isSpam = $isSpam || substr_count($value, 'http://') > $constraint->urlLimit;

		if ($isSpam) {
			$this->setMessage($constraint->message);
		}

		return ! $isSpam;
	}
}
