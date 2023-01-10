<?php namespace App\Controller;

use App\Entity\Person;
use App\Persistence\BookRepository;
use App\Persistence\CountryRepository;
use App\Persistence\PersonRepository;
use App\Pagination\Pager;
use App\Persistence\TextRepository;
use App\Persistence\UserRepository;
use App\Service\SearchService;
use App\Service\Translation;
use App\Service\WikiReader;
use App\Util\Stringy;
use Symfony\Component\HttpFoundation\Request;

class PersonController extends Controller {

	const PAGE_COUNT_DEFAULT = 100;
	const PAGE_COUNT_LIMIT = 1000;

	protected $personRepository;

	public function __construct(UserRepository $userRepository, PersonRepository $personRepository) {
		parent::__construct($userRepository);
		$this->personRepository = $personRepository;
	}

	public function indexAction(CountryRepository $countryRepository) {
		return [
			'countries' => $countryRepository->findAll(),
		];
	}

	public function listByAlphaIndexAction($by) {
		return [
			'by' => $by,
		];
	}

	public function listByAlphaAction(Request $request, $by, $letter, $page) {
		$country = $request->query->get('country', '');
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);

		$repo = $this->getPersonRepository();
		$filters = [
			'by'      => $by,
			'prefix'  => $letter,
			'country' => $country,
		];
		return [
			'by'      => $by,
			'letter'  => $letter,
			'country' => $country,
			'persons' => $repo->getBy($filters, $page, $limit),
			'pager'    => new Pager($page, $repo->countBy($filters), $limit),
			'route_params' => ['letter' => $letter, 'by' => $by],
		];
	}

	public function listByCountryIndexAction(CountryRepository $countryRepository, $by) {
		return [
			'by' => $by,
			'countries' => $countryRepository->findAll(),
		];
	}

	public function listByCountryAction(CountryRepository $countryRepository, Request $request, $country, $by, $page, $_format) {
		$country = $countryRepository->findByCode($country);
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);
		$repo = $this->getPersonRepository();
		$filters = [
			'by'      => $by,
			'country' => $country->getCode(),
		];
		return [
			'by'      => $by,
			'country' => $country,
			'persons' => $repo->getBy($filters, $page, $limit),
			'pager'    => new Pager($page, $repo->countBy($filters), $limit),
			'route_params' => ['country' => $country->getCode(), 'by' => $by, '_format' => $_format],
		];
	}

	public function showAction(TextRepository $textRepository, BookRepository $bookRepository, $slug, $_format, bool $allowRemoteWikiArticle, WikiReader $wikiReader) {
		$person = $this->tryToFindPerson($slug);
		if ( ! $person instanceof Person) {
			return $person;
		}

		return $this->getShowTemplateParams($textRepository, $bookRepository, $person, $_format) + [
			'person' => $person,
		] + $this->getShowTemplateInfoParams($person, $allowRemoteWikiArticle, $wikiReader);
	}

	public function searchAction(SearchService $searchService, Request $request, $_format) {
		if ($_format == 'osd') {
			return [];
		}
		if ($_format == 'suggest') {
			$items = $descs = $urls = [];
			$query = $request->query->get('q');
			$persons = $this->personRepository->getByQuery([
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => self::PAGE_COUNT_LIMIT,
			]);
			foreach ($persons as $person) {
				$items[] = $person['name'];
				$descs[] = '';
				$urls[] = $this->generateAbsoluteUrl('person_show', ['slug' => $person['slug']]);
			}

			return [$query, $items, $descs, $urls];
		}
		$query = $searchService->prepareQuery($request, $_format);
		if (isset($query['_template'])) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'name,origName,realName,origRealName';
		}
		$persons = $this->personRepository->getByQuery($query);
		$found = count($persons) > 0;
		return [
			'query'   => $query,
			'persons' => $persons,
			'found'   => $found,
			'_status' => !$found ? 404 : null,
		];
	}

	public function showInfoAction($slug) {
		$person = $this->tryToFindPerson($slug);
		if (!$person instanceof Person) {
			return $person;
		}
		return [
			'person' => $person,
		];
	}

	protected function tryToFindPerson($slug) {
		$person = $this->personRepository->findBySlug(Stringy::slugify($slug));
		if ($person) {
			return $person;
		}

		$person = $this->personRepository->findOneBy(['name' => $slug]);
		if ($person) {
			return $this->urlRedirect($this->generateUrl('person_show', ['slug' => $person->getSlug()]), true);
		}
		throw $this->createNotFoundException("Няма личност с код $slug.");
	}

	protected function getShowTemplateParams(TextRepository $textRepository, BookRepository $bookRepository, Person $person, $format) {
		return array_merge(
			$this->getShowTemplateParamsAuthor($textRepository, $bookRepository, $person, $format),
			$this->getShowTemplateParamsTranslator($textRepository, $person, $format)
		);
	}
	protected function getShowTemplateParamsAuthor(TextRepository $textRepository, BookRepository $bookRepository, Person $person, $format) {
		$groupBySeries = $format == 'html';
		return [
			'texts_as_author' => $textRepository->findByAuthor($person, $groupBySeries),
			'books' => $bookRepository->findByAuthor($person),
		];
	}
	protected function getShowTemplateParamsTranslator(TextRepository $textRepository, Person $person, $format) {
		return [
			'texts_as_translator' => $textRepository->findByTranslator($person),
		];
	}

	protected function getPersonRepository(): PersonRepository {
		return $this->personRepository;
	}

	/**
	 * @param array $query
	 * @return Person[]
	 */
	protected function findByQuery(array $query) {
		return $this->getPersonRepository()->findByQuery($query);
	}

	private function getShowTemplateInfoParams(Person $person, bool $allowRemoteWikiArticle, WikiReader $wikiReader) {
		if ($person->getInfo() == '' || !$allowRemoteWikiArticle) {
			return [];
		}
		return [
			'wikiPage' => $wikiReader->fetchPage($person->getInfo()),
		];
	}

}
