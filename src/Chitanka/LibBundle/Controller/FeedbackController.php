<?php

namespace Chitanka\LibBundle\Controller;

use Chitanka\LibBundle\Entity\Feedback;
use Chitanka\LibBundle\Form\FeedbackForm;

class FeedbackController extends Controller
{
	public function indexAction()
	{
		$adminEmail = $this->container->getParameter('admin_email');
		$feedback = new Feedback($this->get('mailer'), $adminEmail);

		$form = FeedbackForm::create($this->get('form.context'), 'feedback');

		$form->bind($this->get('request'), $feedback);

		$this->view = array(
			'admin_email' => key($adminEmail),
			'form' => $form,
		);

// 		if ('POST' === $this->get('request')->getMethod()) {
// 			$form->bindAndProcess($this->get('request')->request);
// 		}

		if ($form->isValid()) {
			$form->process();
			$this->view['message'] = 'Съобщението ви беше изпратено.';
// 			$this->mailFailureMessage = 'Изглежда е станал някакъв фал при изпращането на съобщението ви. Ако желаете, пробвайте още веднъж.';
// 			if ( empty($this->referer) ) {
// 				return '';
// 			}
// 			"<p>Обратно към предишната страница</p>";
		}

		return $this->display('index');
	}

}
