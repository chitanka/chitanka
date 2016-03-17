<?php namespace App\Controller;

use App\Entity\Book;
use App\Generator\DownloadFile;
use App\Legacy\Setup;
use App\Pagination\Pager;
use App\Service\SearchService;
use App\Util\Stringy;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\Request;

class BookController extends Controller {

	const PAGE_COUNT_DEFAULT = 30;
	const PAGE_COUNT_LIMIT = 300;

	public function indexAction($_format) {
		if (in_array($_format, ['html', 'json'])) {
			return [
				'categories' => $this->em()->getCategoryRepository()->getAllAsTree(),
			];
		}
		return [];
	}

	public function listByCategoryIndexAction($_format) {
		switch ($_format) {
			case 'html':
				return [
					'categories' => $this->em()->getCategoryRepository()->getAllAsTree(),
				];
			case 'opds':
				return [
					'categories' => $this->em()->getCategoryRepository()->getAll(),
				];
		}
	}

	public function listByAlphaIndexAction() {
		return [];
	}

	public function listByCategoryAction(Request $request, $slug, $page) {
		$slug = Stringy::slugify($slug);
		$bookRepo = $this->em()->getBookRepository();
		$category = $this->em()->getCategoryRepository()->findBySlug($slug);
		if ($category === null) {
			throw $this->createNotFoundException("Няма категория с код $slug.");
		}
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		return [
			'category' => $category,
			'parents' => array_reverse($category->getAncestors()),
			'books' => $bookRepo->findByCategory($category, $page, $limit),
			'pager'    => new Pager($page, $category->getNrOfBooks(), $limit),
			'route_params' => ['slug' => $slug],
		];
	}

	public function listByAlphaAction(Request $request, $letter, $page) {
		$bookRepo = $this->em()->getBookRepository();
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$prefix = $letter == '-' ? null : $letter;
		return [
			'letter' => $letter,
			'books' => $bookRepo->findByPrefix($prefix, $page, $limit),
			'pager'    => new Pager($page, $bookRepo->countByPrefix($prefix), $limit),
			'route_params' => ['letter' => $letter],
		];
	}

	public function listWoCoverAction(Request $request, $page) {
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$bookRepo = $this->em()->getBookRepository();
		return [
			'books' => $bookRepo->findWithMissingCover($page, $limit),
			'pager' => new Pager($page, $bookRepo->getCountWithMissingCover(), $limit),
		];
	}

	public function listByIsbnAction($isbn) {
		$books = $this->em()->getBookRepository()->findByIsbn($isbn);
		if (count($books) == 1) {
			return $this->redirectToRoute('book_show', ['id' => $books[0]->getId()]);
		}
		return [
			'isbn' => $isbn,
			'books' => $books,
		];
	}

	public function showAction($id, $_format) {
		list($id) = explode('-', $id); // remove optional slug
		try {
			$book = $this->em()->getBookRepository()->get($id);
		} catch (NoResultException $e) {
			throw $this->createNotFoundException("Няма книга с номер $id.");
		}

		switch ($_format) {
			case 'sfb.zip':
			case 'txt.zip':
			case 'fb2.zip':
			case 'epub':
				Setup::doSetup($this->container);
				return $this->urlRedirect($this->processDownload($book, $_format));
			case 'djvu':
			case 'pdf':
				return $this->urlRedirect($this->processDownload($book, $_format));
			case 'txt':
				Setup::doSetup($this->container);
				return $this->asText($book->getContentAsTxt());
			case 'fb2':
				Setup::doSetup($this->container);
				return $this->asText($book->getContentAsFb2(), 'application/xml');
			case 'sfb':
				Setup::doSetup($this->container);
				return $this->asText($book->getContentAsSfb());
			case 'data':
				return $this->asText($book->getDataAsPlain());
			case 'opds':
				break;
			case 'pic':
				Setup::doSetup($this->container);
				break;
			case 'html':
			default:
		}

		return [
			'book' => $book,
			'authors' => $book->getAuthors(),
			'template' => $book->getTemplateAsXhtml(),
			'info' => $book->getExtraInfoAsXhtml(),
		];
	}

	public function searchAction(Request $request, $_format) {
		if ($_format == 'osd') {
			return [];
		}
		if ($_format == 'suggest') {
			$items = $descs = $urls = [];
			$query = $request->query->get('q');
			$books = $this->em()->getBookRepository()->findByQuery([
				'text'  => $query,
				'by'    => 'title',
				'match' => 'prefix',
				'limit' => 10,
			]);
			foreach ($books as $book) {
				$items[] = $book->getTitle();
				$descs[] = '';
				$urls[] = $this->generateUrl('book_show', ['id' => $book->getId()], true);
			}

			return $this->asJson([$query, $items, $descs, $urls]);
		}
		$searchService = new SearchService($this->em());
		$query = $searchService->prepareQuery($request, $_format);
		if (isset($query['_template'])) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'title,subtitle,origTitle';
		}
		$books = $this->em()->getBookRepository()->findByQuery($query);
		$found = count($books) > 0;
		return [
			'query' => $query,
			'books' => $books,
			'found' => $found,
			'_status' => !$found ? 404 : null,
		];
	}

	public function randomAction() {
		$id = $this->em()->getBookRepository()->getRandomId();

		return $this->urlRedirect($this->generateUrl('book_show', ['id' => $id]));
	}

	/**
	 * @param Book $book
	 * @param string $format
	 * @return string File URL
	 */
	protected function processDownload(Book $book, $format) {
		$dlSite = $this->getMirrorServer();
		if ( $dlSite !== false ) {
			return "$dlSite/book/{$book->getId()}.$format";
		}

		$dlFile = new DownloadFile;
		switch ($format) {
			case 'sfb.zip':
				return $this->getWebRoot() . $dlFile->getSfbForBook($book);
			case 'txt.zip':
				return $this->getWebRoot() . $dlFile->getTxtForBook($book);
			case 'fb2.zip':
				return $this->getWebRoot() . $dlFile->getFb2ForBook($book);
			case 'epub':
				return $this->getWebRoot() . $dlFile->getEpubForBook($book);
			case 'djvu':
			case 'pdf':
				return $this->getWebRoot() . $dlFile->getStaticFileForBook($book, $format);
		}
		throw $this->createNotFoundException("Книгата не е налична във формат {$format}.");
	}

}
