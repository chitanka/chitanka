<?php namespace App\Controller\Admin;

use App\Entity\Book;
use App\Service\ContentService;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class BookCrudController extends CrudController {
	protected static $entityClass = Book::class;

	// TODO Copied from a Sonata controller, rewrite for EasyAdmin
	public function updateCover(AdminContext $context) {
		$book = $context->getEntity()->getInstance();

		if (!$book) {
			throw $this->createNotFoundException('Unable to locate a book');
		}

		if ($request->isMethod(Request::METHOD_POST)) {
			ContentService::copyCoverFromBiblioman($book);
			$book->setHasCover(true);
			$em = $this->getDoctrine()->getManager();
			$em->persist($book);
			$em->flush();

			$this->addFlash('sonata_flash_success', "Корицата на „{$book}“ беше обновена.");

			return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
		}
		return $this->render('BookAdmin/update_cover.html.twig', [
			'book' => $book,
		]);
	}
}
