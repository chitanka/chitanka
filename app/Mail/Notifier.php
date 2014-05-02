<?php namespace App\Mail;

use Swift_Mailer;
use Swift_Message;

class Notifier {

	private $mailer;

	public function __construct(Swift_Mailer $mailer) {
		$this->mailer = $mailer;
	}

	public function sendPerMail(MailSource $source, $recipient) {
		$message = \Swift_Message::newInstance($source->getSubject())
			->setFrom($source->getSender())
			->setTo($recipient)
			->setBody($source->getBody());

		$headers = $message->getHeaders();
		$headers->addMailboxHeader('Reply-To', $source->getSender());
		$headers->addTextHeader('X-Mailer', 'Chitanka');

		$this->mailer->send($message);
	}

	protected function sendMessage(Swift_Message $message) {
		$this->mailer->send($message);
	}

	/**
	 * @param string $message
	 */
	protected function logError($message) {
		error_log($message);
	}
}
