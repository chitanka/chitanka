<?php

namespace Chitanka\LibBundle\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\NoResultException;
use Chitanka\LibBundle\Entity\Book;
use Chitanka\LibBundle\Pagination\Pager;
use Chitanka\LibBundle\Legacy\Setup;
use Chitanka\LibBundle\Legacy\DownloadFile;
use Chitanka\LibBundle\Util\String;

class BookController extends Controller
{
	protected $repository = 'Book';

	public function indexAction($_format)
	{
		if ($_format == 'html') {
			$this->view = array(
				'categories' => $this->getCategoryRepository()->getAllAsTree(),
			);
        }

		return $this->display("index.$_format");
	}

	public function listByCategoryIndexAction($_format)
	{
		switch ($_format) {
			case 'html':
				$categories = $this->getCategoryRepository()->getAllAsTree();
				break;
			case 'opds':
				$categories = $this->getCategoryRepository()->getAll();
				break;
		}
		$this->view['categories'] = $categories;

		return $this->display("list_by_category_index.$_format");
	}

	public function listByAlphaIndexAction($_format)
	{
		return $this->display("list_by_alpha_index.$_format");
	}

	public function listByCategoryAction($slug, $page, $_format)
	{
		$slug = String::slugify($slug);
		$bookRepo = $this->getBookRepository();
		$category = $this->getCategoryRepository()->findBySlug($slug);
		if ($category === null) {
			throw new NotFoundHttpException("Няма категория с код $slug.");
		}
		$limit = 30;

		$this->view = array(
			'category' => $category,
			'parents' => array_reverse($category->getAncestors()),
			'books' => $bookRepo->getByCategory($category, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $category->getNrOfBooks()
			)),
			'route_params' => array('slug' => $slug),
		);

		return $this->display("list_by_category.$_format");
	}


	public function listByAlphaAction($letter, $page, $_format)
	{
		$bookRepo = $this->getBookRepository();
		$limit = 30;

		$prefix = $letter == '-' ? null : $letter;
		$this->view = array(
			'letter' => $letter,
			'books' => $bookRepo->getByPrefix($prefix, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $bookRepo->countByPrefix($prefix)
			)),
			'route_params' => array('letter' => $letter),
		);

		return $this->display("list_by_alpha.$_format");
	}


	public function showAction($id, $_format)
	{
		// FIXME
		// very big books need too much memory, so give it to them
		ini_set('memory_limit', '128M');

		list($id) = explode('-', $id); // remove optional slug
		try {
			$book = $this->getBookRepository()->get($id);
		} catch (NoResultException $e) {
			throw new NotFoundHttpException("Няма книга с номер $id.");
		}

		switch ($_format) {
			case 'sfb.zip':
			case 'txt.zip':
			case 'fb2.zip':
			case 'epub':
				Setup::doSetup($this->container);
				return $this->urlRedirect($this->processDownload($book, $_format));
			case 'djvu':
				return $this->urlRedirect($this->processDownload($book, $_format));
				break;
			case 'txt':
				Setup::doSetup($this->container);
				return $this->displayText($book->getContentAsTxt(), array('Content-Type' => 'text/plain'));
			case 'fb2':
				Setup::doSetup($this->container);
				return $this->displayText($book->getContentAsFb2(), array('Content-Type' => 'application/xml'));
			case 'sfb':
				Setup::doSetup($this->container);
				return $this->displayText($book->getContentAsSfb(), array('Content-Type' => 'text/plain'));
			case 'clue':
				return $this->displayText($book->getAnnotationAsXhtml());
			case 'opds':
				break;
			case 'html':
			default:
				if ($book->getType() == 'pic') {
					Setup::doSetup($this->container);
				}
		}

		$this->view = array(
			'book' => $book,
			'authors' => $book->getAuthors(),
			'template' => $book->getTemplateAsXhtml(),
			'annotation' => $book->getAnnotationAsXhtml(),
			'info' => $book->getExtraInfoAsXhtml(),
		);

		return $this->display("show.$_format");
	}


	public function randomAction()
	{
		$id = $this->getBookRepository()->getRandomId();

		return $this->urlRedirect($this->generateUrl('book_show', array('id' => $id)));
	}

	protected function processDownload(Book $book, $format)
	{
		$dlSite = $this->getMirrorServer();
		if ( $dlSite !== false ) {
			return sprintf('%s/book/%d.%s', $dlSite, $book->getId(), $format);
		}

		$file = null;
		$dlFile = new DownloadFile;
		switch ($format) {
			case 'sfb.zip':
			default:
				$file = $dlFile->getSfbForBook($book);
				break;
			case 'txt.zip':
				$file = $dlFile->getTxtForBook($book);
				break;
			case 'fb2.zip':
				$file = $dlFile->getFb2ForBook($book);
				break;
			case 'epub':
				$file = $dlFile->getEpubForBook($book);
				break;
			case 'djvu':
				$file = $dlFile->getDjvuForBook($book);
				break;
		}

		return "/$file";
	}

}
