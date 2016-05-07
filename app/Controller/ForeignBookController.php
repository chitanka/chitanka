<?php namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/foreign-books")
 */
class ForeignBookController extends Controller {

	/**
	 * @Route("", name="foreign_books_index")
	 */
	public function indexAction() {
		return [
			'books' => $this->em()->getForeignBookRepository()->getLatest(100)
		];
	}

	/**
	 * @Route("/{id}", name="foreign_books_show")
	 */
	public function showAction($id) {
		$book = $this->em()->getForeignBookRepository()->findOneById($id);
		if ($book === null) {
			throw $this->createNotFoundException();
		}
		return [
			'book' => $book,
		];
	}

}
