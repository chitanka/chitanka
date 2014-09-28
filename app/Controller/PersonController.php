<?php namespace App\Controller;

use App\Pagination\Pager;
use App\Util\String;
use App\Entity\Person;
use App\Service\MediawikiClient;
use App\Service\SearchService;
use Symfony\Component\HttpFoundation\Request;

class PersonController extends Controller {

	public function indexAction() {
		return array();
	}

	public function listByAlphaIndexAction($by) {
		return array(
			'by' => $by,
		);
	}

	public function listByAlphaAction($by, $letter, $page) {
		$request = $this->get('request')->query;
		$country = $request->get('country', '');
		$limit = 100;

		$repo = $this->em()->getPersonRepository();
		$filters = array(
			'by'      => $by,
			'prefix'  => $letter,
			'country' => $country,
		);
		return array(
			'by'      => $by,
			'letter'  => $letter,
			'country' => $country,
			'persons' => $repo->getBy($filters, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countBy($filters)
			)),
			'route_params' => array('letter' => $letter, 'by' => $by),
		);
	}

	public function showAction($slug, $_format) {
		$person = $this->tryToFindPerson($slug);
		if ( ! $person instanceof Person) {
			return $person;
		}

		return $this->getShowTemplateParams($person, $_format) + array(
			'person' => $person,
		) + $this->getShowTemplateInfoParams($person);
	}

	public function searchAction(Request $request, $_format) {
		if ($_format == 'osd') {
			return array();
		}
		if ($_format == 'suggest') {
			$items = $descs = $urls = array();
			$query = $request->query->get('q');
			$persons = $this->em()->getPersonRepository()->getByQuery(array(
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => 10,
			));
			foreach ($persons as $person) {
				$items[] = $person['name'];
				$descs[] = '';
				$urls[] = $this->generateUrl('person_show', array('slug' => $person['slug']), true);
			}

			return $this->asJson(array($query, $items, $descs, $urls));
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
		return array(
			'query'   => $query,
			'persons' => $persons,
			'found'   => $found,
			'_status' => !$found ? 404 : null,
		);
	}

	public function showInfoAction($slug) {
		$person = $this->tryToFindPerson($slug);
		if (!$person instanceof Person) {
			return $person;
		}
		return array(
			'person' => $person,
		);
	}

	protected function tryToFindPerson($slug) {
		$person = $this->em()->getPersonRepository()->findBySlug(String::slugify($slug));
		if ($person) {
			return $person;
		}

		$person = $this->em()->getPersonRepository()->findOneBy(array('name' => $slug));
		if ($person) {
			return $this->urlRedirect($this->generateUrl('person_show', array('slug' => $person->getSlug())), true);
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
		return array(
			'texts_as_author' => $this->em()->getTextRepository()->findByAuthor($person, $groupBySeries),
			'books' => $this->em()->getBookRepository()->getByAuthor($person),
		);
	}
	protected function getShowTemplateParamsTranslator(Person $person, $format) {
		return array(
			'texts_as_translator' => $this->em()->getTextRepository()->findByTranslator($person),
		);
	}

	private function getShowTemplateInfoParams(Person $person) {
		if ($person->getInfo() == '') {
			return array();
		}
		// TODO move this in the entity
		list($prefix, $name) = explode(':', $person->getInfo(), 2);
		$site = $this->em()->getWikiSiteRepository()->findOneBy(array('code' => $prefix));
		$url = $site->getUrl($name);
		$mwClient = new MediawikiClient($this->container->get('buzz'));
		return array(
			'info' => $mwClient->fetchContent($url),
			'info_intro' => strtr($site->getIntro(), array(
				'$1' => $person->getName(),
				'$2' => $url,
			)),
		);
	}

}
