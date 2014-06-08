<?php namespace App\Controller;

use App\Entity\Email;
use App\Form\Type\EmailType;
use App\Mail\Notifier;
use Symfony\Component\HttpFoundation\Request;

class EmailController extends Controller {

	public function newAction(Request $request, $username) {
		$this->responseAge = 0;
		if ( $this->getUser()->isAnonymous() ) {
			return $this->display('stop_anon');
		}
		if ($this->getUser()->getEmail() == '') {
			return $this->display('stop_no_email', array('user' => $this->getUser()));
		}

		$recipientUser = $this->em()->getUserRepository()->findByUsername($username);
		if (!$recipientUser) {
			throw $this->createNotFoundException("Не съществува потребител с име $username.");
		}
		if (!$recipientUser->getAllowemail()) {
			return $this->display('stop_email_not_allowed', array('recipient' => $recipientUser));
		}

		$email = new Email($recipientUser, $this->getUser());
		$form = $this->createForm(new EmailType(), $email);

		if ($this->isValidPost($request, $form)) {
			$notifier = new Notifier($this->get('mailer'));
			$notifier->sendPerMail($email, $email->getRecipient());
			return $this->redirectWithNotice('Писмото ви беше изпратено.');
		}

		return $this->display('new', array(
			'form' => $form->createView(),
			'sender' => $this->getUser(),
			'recipient' => $recipientUser,
		));
	}
}
