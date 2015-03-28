<?php namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class NotSpam extends Constraint {
	public $message = 'notspam';
	public $urlLimit = 2;
	public $stopWords = [
		'CASH & CARRY',
		'НЕ ОТВЕЧАЙТЕ НА ЭТО ПИСЬМО',
		' еба ',
	];
}
