<?php namespace App\Controller;

use App\Pagination\Pager;
use App\Util\String;
use App\Entity\Person;
use App\Service\SearchService;
use Symfony\Component\HttpFoundation\Request;

class PersonController extends Controller {

	public function indexAction() {
		return [
			'countries' => $this->getPersonRepository()->getCountsByCountry(),
		];
	}

	public function listByAlphaIndexAction($by) {
		return [
			'by' => $by,
		];
	}

	public function listByAlphaAction($by, $letter, $page) {
		$request = $this->get('request')->query;
		$country = $request->get('country', '');
		$limit = 100;

		$repo = $this->em()->getPersonRepository();
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
			'pager'    => new Pager([
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countBy($filters)
			]),
			'route_params' => ['letter' => $letter, 'by' => $by],
		];
	}

	public function listByCountryIndexAction($by) {
		return [
			'by' => $by,
			'countries' => $this->getPersonRepository()->getCountryList()
		];
	}

	public function listByCountryAction($country, $by, $page, $_format) {
		$limit = 100;

		$repo = $this->getPersonRepository();
		$filters = [
			'by'      => $by,
			'country' => $country,
		];
		return [
			'by'      => $by,
			'country' => $country,
			'persons' => $repo->getBy($filters, $page, $limit),
			'pager'    => new Pager([
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countBy($filters)
			]),
			'route_params' => ['country' => $country, 'by' => $by, '_format' => $_format],
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
				'limit' => 10,
			]);
			foreach ($persons as $person) {
				$items[] = $person['name'];
				$descs[] = '';
				$urls[] = $this->generateUrl('person_show', ['slug' => $person['slug']], true);
			}

			return $this->asJson([$query, $items, $descs, $urls]);
		}
		$searchService = new SearchService($this->em());
		$query = $searchService->prepareQuery($request, $_format);
		if (isset($query['_template'])) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'name,orig_name,real_name,orig_real_name';
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
		$person = $this->em()->getPersonRepository()->findBySlug(String::slugify($slug));
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
			'books' => $this->em()->getBookRepository()->getByAuthor($person),
		];
	}
	protected function getShowTemplateParamsTranslator(Person $person, $format) {
		return [
			'texts_as_translator' => $this->em()->getTextRepository()->findByTranslator($person),
		];
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
