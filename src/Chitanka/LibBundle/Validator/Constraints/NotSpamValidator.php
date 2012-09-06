<?php
namespace Chitanka\LibBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NotSpamValidator extends ConstraintValidator
{
	public function isValid($value, Constraint $constraint)
	{
		$isSpam = false;
		$isSpam = $isSpam || $this->containsUrl($value);
		$isSpam = $isSpam || $this->containsTooManyUrls($value, $constraint->urlLimit);
		$isSpam = $isSpam || $this->containsStopWords($value, $constraint->stopWords);

		if ($isSpam) {
			$this->setMessage($constraint->message);
		}

		return ! $isSpam;
	}

	private function containsUrl($value)
	{
		return strpos($value, 'href=') !== false || strpos($value, 'url=') !== false;
	}

	private function containsTooManyUrls($value, $allowedCount)
	{
		return substr_count($value, 'http://') > $allowedCount;
	}

	private function containsStopWords($value, array $stopWords)
	{
		foreach ($stopWords as $stopWord) {
			if (strpos($value, $stopWord) !== false) {
				return true;
			}
		}
		return false;
	}
}
