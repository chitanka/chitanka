<?php namespace App\Controller;

use App\Entity\Feedback;
use App\Form\Type\FeedbackType;
use App\Mail\Notifier;
use Symfony\Component\HttpFoundation\Request;

class FeedbackController extends Controller {

	public function indexAction(Request $request) {
		$form = $this->createForm(FeedbackType::class , new Feedback());
		$adminEmail = $this->container->getParameter('admin_email');

		$form->handleRequest($request);
		if ($form->isValid()) {
			$notifier = new Notifier($this->get('mailer'));
			$notifier->sendPerMail($form->getData(), $adminEmail);
			$this->flashes()->addNotice('Съобщението ви беше изпратено.');
			return $this->redirectToRoute('feedback');
		}

		return [
			'admin_email' => key($adminEmail),
			'form' => $form->createView(),
		];
	}

}
