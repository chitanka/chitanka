<?php namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\Entity\Feedback;
use App\Form\Type\FeedbackType;
use App\Mail\Notifier;

class FeedbackController extends Controller {

	public function indexAction(Request $request) {
		$form = $this->createForm(new FeedbackType, new Feedback());
		$adminEmail = $this->container->getParameter('admin_email');

		$message = null;
		if ($request->isMethod('POST') && $form->submit($request)->isValid()) {
			$notifier = new Notifier($this->get('mailer'));
			$notifier->sendPerMail($form->getData(), $adminEmail);
			$message = 'Съобщението ви беше изпратено.';
//			if ( empty($this->referer) ) {
//				return '';
//			}
//			"<p>Обратно към предишната страница</p>";
//			return $this->redirectToRoute('task_success');
		}

		return [
			'admin_email' => key($adminEmail),
			'form' => $form->createView(),
			'message' => $message,
		];
	}

}
