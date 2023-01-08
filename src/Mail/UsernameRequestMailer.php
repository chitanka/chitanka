<?php namespace App\Mail;

use App\Entity\User;

class UsernameRequestMailer extends Notifier {

	private $twig;

	public function __construct(\Swift_Mailer $mailer, \Twig\Environment $twig) {
		parent::__construct($mailer);
		$this->twig = $twig;
	}

	/**
	 * Send a new password to a given user
	 * @param User $user
	 * @param string $sender
	 */
	public function sendUsername(User $user, $sender) {
		/** @var $template \Twig\Template */
		$template = $this->twig->loadTemplate('App:Mail:request_username.txt.twig');
		$templateParams = ['user' => $user];
		$message = \Swift_Message::newInstance($template->renderBlock('subject', $templateParams));
		$message->setFrom($sender, 'Моята библиотека');
		$message->setTo($user->getEmail(), $user->getUsername());
		$message->setBody($template->renderBlock('body', $templateParams));

		$this->sendMessage($message);
	}

}
