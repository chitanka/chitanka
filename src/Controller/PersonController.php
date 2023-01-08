<?php namespace App\Controller;

use App\Entity\Person;
use App\Persistence\PersonRepository;
use App\Pagination\Pager;
use App\Service\SearchService;
use App\Service\Translation;
use App\Util\Stringy;
use Symfony\Component\HttpFoundation\Request;

class PersonController extends Controller {

	const PAGE_COUNT_DEFAULT = 100;
	const PAGE_COUNT_LIMIT = 1000;

	public function indexAction() {
		return [
			'countries' => $this->em()->getCountryRepository()->findAll(),
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

	public function listByCountryIndexAction($by) {
		return [
			'by' => $by,
			'countries' => $this->em()->getCountryRepository()->findAll(),
		];
	}

	public function listByCountryAction(Request $request, $country, $by, $page, $_format) {
		$country = $this->em()->getCountryRepository()->findByCode($country);
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

	public function showAction($slug, $_format) {
		$person = $this->tryToFindPerson($slug);
		if ( ! $person instanceof Person) {
			return $person;
		}

		return $this->getShowTemplateParams($person, $_format) + [
			'person' => $person,
		] + $this->getShowTemplateInfoParams($person);
	}

	public function searchAction(Request $request, $_format) {
		if ($_format == 'osd') {
			return [];
		}
		if ($_format == 'suggest') {
			$items = $descs = $urls = [];
			$query = $request->query->get('q');
			$persons = $this->em()->getPersonRepository()->getByQuery([
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
		$searchService = new SearchService($this->em());
		$query = $searchService->prepareQuery($request, $_format);
		if (isset($query['_template'])) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'name,origName,realName,origRealName';
		}
		$persons = $this->em()->getPersonRepository()->getByQuery($query);
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
		$person = $this->em()->getPersonRepository()->findBySlug(Stringy::slugify($slug));
		if ($person) {
			return $person;
		}

		$person = $this->em()->getPersonRepository()->findOneBy(['name' => $slug]);
		if ($person) {
			return $this->urlRedirect($this->generateUrl('person_show', ['slug' => $person->getSlug()]), true);
		}
		throw $this->createNotFoundException("Няма личност с код $slug.");
	}

	protected function getShowTemplateParams(Person $person, $format) {
		return array_merge(
			$this->getShowTemplateParamsAuthor($person, $format),
			$this->getShowTemplateParamsTranslator($person, $format)
		);
	}
	protected function getShowTemplateParamsAuthor(Person $person, $format) {
		$groupBySeries = $format == 'html';
		return [
			'texts_as_author' => $this->em()->getTextRepository()->findByAuthor($person, $groupBySeries),
			'books' => $this->em()->getBookRepository()->findByAuthor($person),
		];
	}
	protected function getShowTemplateParamsTranslator(Person $person, $format) {
		return [
			'texts_as_translator' => $this->em()->getTextRepository()->findByTranslator($person),
		];
	}

	/** @return PersonRepository */
	protected function getPersonRepository() {
		return $this->em()->getPersonRepository();
	}

	/**
	 * @param array $query
	 * @return Person[]
	 */
	protected function findByQuery(array $query) {
		return $this->getPersonRepository()->findByQuery($query);
	}

	private function getShowTemplateInfoParams(Person $person) {
		if ($person->getInfo() == '' || !$this->container->getParameter('allow_remote_wiki_article')) {
			return [];
		}
		return [
			'wikiPage' => $this->container->get('wiki_reader')->fetchPage($person->getInfo()),
		];
	}

}
