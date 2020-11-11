<?php namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/text-combinations")
 */
class TextCombinationController extends Controller {

	/**
	 * @Route("", methods={"POST"})
	 */
	public function save(Request $request) {
		if ( ! $this->getUser()->inGroup(User::GROUP_EDIT_JUXTAPOSED_TEXTS)) {
			throw $this->createAccessDeniedException();
		}
		$content = json_decode($request->getContent(), true);
		$combination = $this->em()->getTextCombinationRepository()->saveFromArray($content);
		return $this->asJson($combination);
	}

}
