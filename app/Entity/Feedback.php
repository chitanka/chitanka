<?php namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as AppAssert;
use App\Mail\MailSource;

class Feedback implements MailSource {

	public $referer;

	/**
	 * @Assert\Length(min=3)
	 */
	public $name;

	/**
	 * @Assert\Email()
	 */
	public $email;

	/**
	 * @Assert\NotBlank()
	 */
	public $subject = 'Обратна връзка от Моята библиотека';

	/**
	 * @Assert\NotBlank()
	 * @Assert\Length(min=80)
	 * @AppAssert\NotSpam()
	 */
	public $comment;

	public function getBody() {
		return $this->comment;
	}

	public function getSender() {
		$fromEmail = $this->email ?: self::ANONYMOUS_EMAIL;
		$fromName = $this->name ?: self::ANONYMOUS_NAME;
		return array($fromEmail => $fromName);
	}

	public function getSubject() {
		return $this->subject;
	}

}
