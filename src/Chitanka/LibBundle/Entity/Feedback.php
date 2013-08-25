<?php

namespace Chitanka\LibBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Chitanka\LibBundle\Validator\Constraints as MyAssert;

class Feedback
{
	public $referer;

	/**
	 * @Assert\Length(min=3)
	 */
	public $name;

	/**
	 * @Assert\Email()
	 */
	public $email;

	/** */
	public $subject = 'Обратна връзка от Моята библиотека';

	/**
	 * @Assert\NotBlank()
	 * @Assert\Length(min=80)
	 * @MyAssert\NotSpam()
	 */
	public $comment;

	private $mailer;
	private $recipient;


	public function __construct(\Swift_Mailer $mailer, $recipient)
	{
		$this->mailer = $mailer;
		$this->recipient = $recipient;
	}

	public function process()
	{
		$fromEmail = empty($this->email) ? 'anonymous@anonymous.net' : $this->email;
		$fromName = empty($this->name) ? 'Анонимен' : $this->name;
		$sender = array($fromEmail => $fromName);

		$message = \Swift_Message::newInstance($this->subject)
			->setFrom($sender)
			->setTo($this->recipient)
			->setBody($this->comment);

		$headers = $message->getHeaders();
		$headers->addMailboxHeader('Reply-To', $sender);
		$headers->addTextHeader('X-Mailer', 'Chitanka');

		$this->mailer->send($message);
	}
}
