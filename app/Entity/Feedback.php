<?php namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as AppAssert;
use App\Mail\MailSource;

class Feedback extends MailSource {

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

}
