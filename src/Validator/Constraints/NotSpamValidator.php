<?php namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NotSpamValidator extends ConstraintValidator {

	public function validate($value, Constraint $constraint) {
		if ($this->isSpam($value, $constraint)) {
			$this->context->addViolation($constraint->message);
			return false;
		}
		return true;
	}

	private function isSpam($value, NotSpam $constraint) {
		if ($this->containsUrlTag($value)) {
			return true;
		}
		if ($this->containsTooManyUrls($value, $constraint->urlLimit)) {
			return true;
		}
		if ($this->containsStopWords($value, $constraint->stopWords)) {
			return true;
		}
		return false;
	}

	private function containsUrlTag($value) {
		return strpos($value, 'href=') !== false || strpos($value, 'url=') !== false;
	}

	private function containsTooManyUrls($value, $allowedCount) {
		return substr_count($value, 'http://') > $allowedCount;
	}

	private function containsStopWords($value, array $stopWords) {
		$delim = '#';
		foreach ($stopWords as $stopWord) {
			$re = $delim . str_replace($delim, '\\' . $delim, $stopWord) . $delim . 'iu';
			if (preg_match($re, $value)) {
				return true;
			}
		}
		return false;
	}
}
