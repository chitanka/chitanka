<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\Entity\Feedback;
use App\Form\Type\FeedbackType;

class FeedbackController extends Controller
{
	protected $responseAge = 86400; // 24 hours

	public function indexAction(Request $request)
	{
		$adminEmail = $this->container->getParameter('admin_email');
		$feedback = new Feedback($this->get('mailer'), $adminEmail);

		$form = $this->createForm(new FeedbackType, $feedback);

		if ($request->getMethod() == 'POST') {
			$form->bind($request);

			if ($form->isValid()) {
				$form->getData()->process();
				$this->view['message'] = 'Съобщението ви беше изпратено.';
//				$this->mailFailureMessage = 'Изглежда е станал някакъв фал при изпращането на съобщението ви. Ако желаете, пробвайте още веднъж.';
//				if ( empty($this->referer) ) {
//					return '';
//				}
//				"<p>Обратно към предишната страница</p>";
//				return $this->redirect($this->generateUrl('task_success'));
			}
		}

		$this->view['admin_email'] = key($adminEmail);
		$this->view['form'] = $form->createView();

		return $this->display('index');
	}

}
