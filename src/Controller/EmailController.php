<?php namespace App\Controller;

use App\Entity\Email;
use App\Form\Type\EmailType;
use App\Mail\Notifier;
use App\Persistence\UserRepository;
use Symfony\Component\HttpFoundation\Request;

class EmailController extends Controller {

	public function newAction(UserRepository $userRepository, Request $request, $username) {
		$senderUser = $this->getUser();
		if ($senderUser->isAnonymous()) {
			return ['message' => 'stop_anon'];
		}
		if (!$senderUser->hasEmail()) {
			return ['message' => 'stop_no_email', 'sender' => $senderUser];
		}

		$recipientUser = $userRepository->findByUsername($username);
		if (!$recipientUser) {
			throw $this->createNotFoundException("Не съществува потребител с име $username.");
		}
		if (!$recipientUser->isEmailValid()) {
			return ['message' => 'stop_email_not_valid', 'recipient' => $recipientUser];
		}
		if (!$recipientUser->allowsEmail()) {
			return ['message' => 'stop_email_not_allowed', 'recipient' => $recipientUser];
		}

		$email = new Email($recipientUser, $senderUser);
		$form = $this->createForm(EmailType::class, $email);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$notifier = new Notifier($this->get('mailer'));
			$notifier->sendPerMail($email, $email->getRecipient());
			return $this->redirectWithNotice('Писмото ви беше изпратено.');
		}

		return [
			'form' => $form->createView(),
			'sender' => $senderUser,
			'recipient' => $recipientUser,
		];
	}
}
