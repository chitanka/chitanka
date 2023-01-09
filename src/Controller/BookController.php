<?php namespace App\Controller;

use App\Entity\BaseWork;
use App\Entity\Book;
use App\Generator\DownloadFile;
use App\Generator\DownloadUrlGenerator;
use App\Legacy\Setup;
use App\Pagination\Pager;
use App\Persistence\BookRepository;
use App\Persistence\CategoryRepository;
use App\Persistence\UserRepository;
use App\Service\ContentService;
use App\Service\SearchService;
use App\Util\Stringy;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/books")
 */
class BookController extends Controller {

	const PAGE_COUNT_DEFAULT = 30;
	const PAGE_COUNT_LIMIT = 300;

	private $bookRepository;

	public function __construct(UserRepository $userRepository, BookRepository $bookRepository) {
		parent::__construct($userRepository);
		$this->bookRepository = $bookRepository;
	}

	public function indexAction(CategoryRepository $categoryRepository, $_format) {
		if (in_array($_format, ['html', 'json'])) {
			return [
				'categories' => $categoryRepository->getAllAsTree(),
			];
		}
		return [];
	}

	public function listByCategoryIndexAction(CategoryRepository $categoryRepository, $_format) {
		switch ($_format) {
			case 'html':
			case 'opds':
				return [
					'categories' => $categoryRepository->getAll(),
				];
		}
	}

	public function listByAlphaIndexAction() {
		return [];
	}

	public function listByCategoryAction(CategoryRepository $categoryRepo, Request $request, $slug, $page) {
		$slug = Stringy::slugify($slug);
		$bookRepo = $this->bookRepository;
		$category = $categoryRepo->findBySlug($slug);
		if ($category === null) {
			throw $this->createNotFoundException("Няма категория с код $slug.");
		}
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$sorting = $bookRepo->createSortingDefinition($this->readOptionOrParam(self::PARAM_SORT, 'book'));
		return [
			'category' => $category,
			'parents' => array_reverse($categoryRepo->findCategoryAncestors($category)),
			'books' => $bookRepo->findByCategory($categoryRepo->getCategoryDescendantIdsWithSelf($category), $page, $limit, $sorting),
			'pager' => new Pager($page, $category->getNrOfBooks(), $limit),
			'route_params' => ['slug' => $slug],
			'sorting' => $sorting,
		];
	}

	public function listByAlphaAction(Request $request, $letter, $page) {
		$bookRepo = $this->bookRepository;
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$prefix = $letter == '-' ? null : $letter;
		$sorting = $bookRepo->createSortingDefinition($this->readOptionOrParam(self::PARAM_SORT, 'book'));
		return [
			'letter' => $letter,
			'books' => $bookRepo->findByPrefix($prefix, $page, $limit, $sorting),
			'pager'    => new Pager($page, $bookRepo->countByPrefix($prefix), $limit),
			'route_params' => ['letter' => $letter],
			'sorting' => $sorting,
		];
	}

	public function listWoCoverAction(Request $request, $page) {
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$bookRepo = $this->bookRepository;
		$sorting = $bookRepo->createSortingDefinition($this->readOptionOrParam(self::PARAM_SORT, 'book'));
		return [
			'books' => $bookRepo->findWithMissingCover($page, $limit, $sorting),
			'pager' => new Pager($page, $bookRepo->getCountWithMissingCover(), $limit),
			'sorting' => $sorting,
		];
	}

	/**
	 * @Route("/wo-biblioman/{page}.{_format}", name="books_wo_biblioman", defaults={"page": 1, "_format": "html"})
	 */
	public function listWoBibliomanAction(Request $request, $page) {
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$bookRepo = $this->bookRepository;
		$sorting = $bookRepo->createSortingDefinition($this->readOptionOrParam(self::PARAM_SORT, 'book'));
		return [
			'books' => $bookRepo->findWithMissingBibliomanId($page, $limit, $sorting),
			'pager' => new Pager($page, $bookRepo->getCountWithMissingBibliomanId(), $limit),
			'sorting' => $sorting,
		];
	}

	public function listByIsbnAction($isbn) {
		$books = $this->bookRepository->findByIsbn($isbn);
		if (count($books) == 1) {
			return $this->redirectToRoute('book_show', ['id' => $books[0]->getId()]);
		}
		return [
			'isbn' => $isbn,
			'books' => $books,
		];
	}

	public function showAction(Request $request, $id, $_format) {
		[$id] = explode('-', $id); // remove optional slug
		try {
			$book = $this->bookRepository->get($id);
		} catch (NoResultException $e) {
			throw $this->createNotFoundException("Няма книга с номер $id.");
		}
		if ($book->isBlocked() && $_format !== 'html') {
			return $this->redirectToRoute('book_show', ['id' => $book->getId()]);
		}

		switch ($_format) {
			case 'sfb.zip':
			case 'txt.zip':
			case 'fb2.zip':
			case 'epub':
				Setup::doSetup($this->container);
				return $this->urlRedirect($this->processDownload($book, $_format, $request->getScheme()));
			case 'djvu':
				return $this->urlRedirect($this->processDownload($book, $_format, $request->getScheme()));
			case 'pdf':
				if ($book->hasCustomPdf()) {
					return $this->urlRedirect($this->processDownload($book, $_format, $request->getScheme()));
				}
				break;
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
			case 'cover':
				return $this->urlRedirect('/'.ContentService::getCover($book->hasCover() ? $book->getId() : 0, $request->get('size', 300)));
		}

		$converterSettings = $this->container->getParameter('converter_download');
		$converterFormatKey = "{$_format}_enabled";
		if (isset($converterSettings[$converterFormatKey])) {
			if ( ! $converterSettings[$converterFormatKey]) {
				throw $this->createNotFoundException("Няма поддръжка на формата {$_format}.");
			}
			return $this->urlRedirect($this->generateConverterUrl($book, $_format));
		}

		return [
			'book' => $book,
			'authors' => $book->getAuthors(),
			'template' => $book->getTemplateAsXhtml(),
			'info' => $book->getExtraInfoAsXhtml(),
			'js_extra' => ['book'],
		];
	}

	public function searchAction(SearchService $searchService, Request $request, $_format) {
		if ($_format == 'osd') {
			return [];
		}
		if ($_format == 'suggest') {
			$query = $request->query->get('q');
			$books = $this->findByQuery([
				'text'  => $query,
				'by'    => 'title,subtitle,origTitle',
				'match' => 'prefix',
				'limit' => self::PAGE_COUNT_LIMIT,
			]);
			$items = $descs = $urls = [];
			foreach ($books as $book) {
				$authors = $book->getAuthorNamesString();
				$items[] = $book->getTitle() . ($authors ? " – $authors" : '');
				$descs[] = '';
				$urls[] = $this->generateAbsoluteUrl('book_show', ['id' => $book->getId()]);
			}

			return [$query, $items, $descs, $urls];
		}
		$query = $searchService->prepareQuery($request, $_format);
		if (isset($query['_template'])) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'title,subtitle,origTitle';
		}
		$books = $this->findByQuery($query);
		$found = count($books) > 0;
		return [
			'query' => $query,
			'books' => $books,
			'found' => $found,
			'_status' => !$found ? 404 : null,
		];
	}

	public function randomAction() {
		$id = $this->bookRepository->getRandomId();

		return $this->urlRedirect($this->generateUrl('book_show', ['id' => $id]));
	}

	/**
	 * @param Book $book
	 * @param string $format
	 * @return string File URL
	 */
	protected function processDownload(Book $book, $format, $requestedScheme) {
		$dlSite = $this->getMirrorServer();
		if ( $dlSite !== false ) {
			if (substr($dlSite, 0, 2) === '//') {
				$dlSite = $requestedScheme .':'. $dlSite;
			}
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

	protected function generateConverterUrl(Book $book, string $targetFormat): string {
		$epubUrl = $this->generateAbsoluteUrl('book_show', ['id' => $book->getId(), '_format' => Book::FORMAT_EPUB]);
		$mirrors = $this->container->getParameter('mirror_sites_for_converter') ?: [];
		return (new DownloadUrlGenerator())->generateConverterUrl($epubUrl, $targetFormat, $mirrors);
	}

	/**
	 * @param array $query
	 * @return Book[]
	 */
	protected function findByQuery(array $query) {
		return $this->bookRepository->findByQuery($query);
	}
}
