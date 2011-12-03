<?php

namespace Chitanka\LibBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Chitanka\LibBundle\Entity\Feedback;
use Chitanka\LibBundle\Form\Type\FeedbackType;

class FeedbackController extends Controller
{
	public function indexAction(Request $request)
	{
		$adminEmail = $this->container->getParameter('admin_email');
		$feedback = new Feedback($this->get('mailer'), $adminEmail);

		$form = $this->createForm(new FeedbackType, $feedback);

		$this->view = array(
			'admin_email' => key($adminEmail),
			'form' => $form->createView(),
		);

		if ($request->getMethod() == 'POST') {
			$form->bindRequest($request);

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

		return $this->display('index');
	}

}
