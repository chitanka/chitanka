<?php namespace App\Mail;

use App\Entity\User;

class PasswordRequestMailer extends Notifier {

	private $twig;

	public function __construct(\Swift_Mailer $mailer, \Twig_Environment $twig) {
		parent::__construct($mailer);
		$this->twig = $twig;
	}

	public function sendNewPassword(User $user, $newPassword, $sender) {
		$template = $this->twig->loadTemplate('App:Mail:request_password.txt.twig');
		$templateParams = array('newPassword' => $newPassword);
		$message = \Swift_Message::newInstance($template->renderBlock('subject', $templateParams))
			->setFrom($sender, 'Моята библиотека')
			->setTo($user->getEmail(), $user->getUsername())
			->setBody($template->renderBlock('body', $templateParams));

		$this->sendMessage($message);
	}

}
