<?php namespace App\Entity;

use App\Mail\MailSource;
use App\Validator\Constraints as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

class Email extends MailSource {

	/**
	 * @Assert\NotBlank()
	 */
	public $subject = 'Писмо чрез Моята библиотека';

	/**
	 * @Assert\NotBlank()
	 * @AppAssert\NotSpam()
	 */
	public $message;

	private $recipientUser;
	private $senderUser;

	/**
	 * @param User $recipientUser
	 * @param User $senderUser
	 */
	public function __construct(User $recipientUser, User $senderUser) {
		$this->recipientUser = $recipientUser;
		$this->senderUser = $senderUser;
	}

	public function getSenderEmail() {
		return $this->senderUser->getEmail();
	}

	public function getSenderName() {
		return $this->senderUser->getUsername();
	}

	public function getRecipient() {
		return [$this->recipientUser->getEmail() => $this->recipientUser->getUsername()];
	}

	public function getBody() {
		return <<<EOS
$this->message

----
Това писмо е изпратено от {$this->getSenderName()} <{$this->getSenderEmail()}> чрез Моята библиотека (http://chitanka.info).
EOS;
	}

}
