<?php
namespace Chitanka\LibBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class NotSpam extends Constraint
{
	public $message = 'notspam';
	public $urlLimit = 2;
	public $stopWords = array(
		'CASH & CARRY',
		'НЕ ОТВЕЧАЙТЕ НА ЭТО ПИСЬМО',
	);
}
