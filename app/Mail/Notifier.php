<?php namespace App\Mail;

use Swift_Mailer;
use Swift_Message;

class Notifier {

	private $mailer;

	public function __construct(Swift_Mailer $mailer) {
		$this->mailer = $mailer;
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
