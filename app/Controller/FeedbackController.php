<?php namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\Entity\Feedback;
use App\Form\Type\FeedbackType;
use App\Mail\Notifier;

class FeedbackController extends Controller {

	public function indexAction(Request $request) {
		$form = $this->createForm(new FeedbackType, new Feedback());
		$adminEmail = $this->getParameter('admin_email');

		if ($request->isMethod('POST') && $form->submit($request)->isValid()) {
			$notifier = new Notifier($this->get('mailer'));
			$notifier->sendPerMail($form->getData(), $adminEmail);
			$this->view['message'] = 'Съобщението ви беше изпратено.';
//			if ( empty($this->referer) ) {
//				return '';
//			}
//			"<p>Обратно към предишната страница</p>";
//			return $this->redirect($this->generateUrl('task_success'));
		}

		$this->view['admin_email'] = key($adminEmail);
		$this->view['form'] = $form->createView();

		return $this->display('index');
	}

}
