<?php namespace App\Controller\Admin;

use App\Service\ContentService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookController extends CRUDController {

	/**
	 * @param int $id
	 * @return Response
	 */
	public function updateCoverAction(Request $request, $id) {
		$object = $this->admin->getSubject();

		if (!$object) {
			throw new NotFoundHttpException(sprintf('unable to find the object with id: %s', $id));
		}

		if ($request->isMethod(Request::METHOD_POST)) {
			ContentService::copyCoverFromBiblioman($object);
			$object->setHasCover(true);
			$em = $this->getDoctrine()->getManager();
			$em->persist($object);
			$em->flush();

			$this->addFlash('sonata_flash_success', "Корицата на „{$object}“ беше обновена.");

			return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
		}
		return $this->render('BookAdmin/update_cover.html.twig', [
			'object' => $object,
		]);
	}

}
