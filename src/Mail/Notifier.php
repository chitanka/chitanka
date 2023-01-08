<?php namespace App\Mail;

use Swift_Mailer;
use Swift_Message;

class Notifier {

	private $mailer;

	public function __construct(Swift_Mailer $mailer) {
		$this->mailer = $mailer;
	}

	public function sendPerMail(MailSource $source, $recipient) {
		$message = \Swift_Message::newInstance($source->getSubject());
		$message->setFrom($source->getSenderEmail(), $source->getSenderName());
		$message->setTo($recipient);
		$message->setBody($source->getBody());

		$this->sendMessage($message);
	}

	public function sendMessage(Swift_Message $message) {
		$message->setReplyTo($message->getFrom());
		$message->getHeaders()->addTextHeader('X-Mailer', 'Chitanka');
		$this->mailer->send($message);
	}

	/**
	 * @param string $message
	 */
	protected function logError($message) {
		error_log($message);
	}
}
