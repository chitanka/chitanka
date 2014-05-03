<?php namespace App\Controller;

use App\Entity\InfoSuggestion;
use App\Form\Type\InfoSuggestionType;
use App\Mail\Notifier;
use Symfony\Component\HttpFoundation\Request;

class InfoSuggestionController extends Controller {

	public function indexAction(Request $request, $type, $id) {
		try {
			$text = $this->getTextRepository()->get($id);
			$infoSuggestion = new InfoSuggestion($type, $text);
			$infoSuggestion->setSender($this->getUser());
		} catch (\InvalidArgumentException $e) {
			return $this->notFound();
		}
		$form = $this->createForm(new InfoSuggestionType(), $infoSuggestion);

		if ($this->isValidPost($request, $form)) {
			$notifier = new Notifier($this->get('mailer'));
			$notifier->sendPerMail($infoSuggestion, $this->getParameter('work_email'));
			return $this->redirectWithNotice('Съобщението ви беше изпратено.');
		}

		return $this->display('index', array(
			'form' => $form->createView(),
			'type' => $type,
			'text' => $text,
		));
	}
}
