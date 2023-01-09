<?php namespace App\Controller;

use App\Entity\InfoSuggestion;
use App\Form\Type\InfoSuggestionType;
use App\Mail\Notifier;
use App\Persistence\TextRepository;
use Symfony\Component\HttpFoundation\Request;

class InfoSuggestionController extends Controller {

	public function indexAction(TextRepository $textRepository, Request $request, $type, $id) {
		try {
			$text = $textRepository->get($id);
			$infoSuggestion = new InfoSuggestion($type, $text);
			$infoSuggestion->setSender($this->getUser());
		} catch (\InvalidArgumentException $e) {
			throw $this->createNotFoundException();
		}
		$form = $this->createForm(InfoSuggestionType::class, $infoSuggestion);

		if ($form->handleRequest($request)->isValid()) {
			$notifier = new Notifier($this->get('mailer'));
			$notifier->sendPerMail($infoSuggestion, $this->container->getParameter('work_email'));
			return $this->redirectWithNotice('Съобщението ви беше изпратено.');
		}

		return [
			'form' => $form->createView(),
			'type' => $type,
			'text' => $text,
		];
	}
}
