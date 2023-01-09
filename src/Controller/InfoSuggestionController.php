<?php namespace App\Controller;

use App\Entity\InfoSuggestion;
use App\Form\Type\InfoSuggestionType;
use App\Mail\Notifier;
use App\Persistence\TextRepository;
use Symfony\Component\HttpFoundation\Request;

class InfoSuggestionController extends Controller {

	public function indexAction(TextRepository $textRepository, Request $request, $type, $id, string $workEmail) {
		try {
			$text = $textRepository->get($id);
			$infoSuggestion = new InfoSuggestion($type, $text);
			$infoSuggestion->setSender($this->getUser());
		} catch (\InvalidArgumentException $e) {
			throw $this->createNotFoundException();
		}
		$form = $this->createForm(InfoSuggestionType::class, $infoSuggestion);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$notifier = new Notifier($this->get('mailer'));
			$notifier->sendPerMail($infoSuggestion, $workEmail);
			return $this->redirectWithNotice('Съобщението ви беше изпратено.');
		}

		return [
			'form' => $form->createView(),
			'type' => $type,
			'text' => $text,
		];
	}
}
