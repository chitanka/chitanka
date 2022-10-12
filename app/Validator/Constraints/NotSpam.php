<?php namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class NotSpam extends Constraint {
	public $message = 'notspam';
	public $urlLimit = 2;
	public $stopWords = [];
	private $stopWordsFile = __DIR__.'/../../config/spam_phrases.ini';

	public function __construct($options = null) {
		parent::__construct($options);
		$this->stopWords = $this->fetchStopWords();
	}

	private function fetchStopWords() {
		$lines = file($this->stopWordsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		return array_filter($lines, function(string $line) {
			return $line[0] !== '#';
		});
	}
}
