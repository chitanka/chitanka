<?php namespace App\Controller;

use App\Entity\User;
use App\Persistence\TextCombinationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/text-combinations")
 */
class TextCombinationController extends Controller {

	/**
	 * @Route("", methods={"POST"})
	 */
	public function save(TextCombinationRepository $textCombinationRepository, Request $request) {
		if ( ! $this->getUser()->inGroup(User::GROUP_EDIT_JUXTAPOSED_TEXTS)) {
			throw $this->createAccessDeniedException();
		}
		$content = json_decode($request->getContent(), true);
		$combination = $textCombinationRepository->saveFromArray($content);
		return $this->asJson($combination);
	}

}
