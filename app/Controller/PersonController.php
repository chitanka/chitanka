<?php namespace App\Controller;

use App\Pagination\Pager;
use App\Legacy\Legacy;
use App\Util\String;
use App\Entity\Person;

class PersonController extends Controller {

	public function indexAction($_format) {
		return $this->display("index.$_format");
	}

	public function listByAlphaIndexAction($by, $_format) {
		$this->view = array(
			'by' => $by,
		);

		return $this->display("list_by_alpha_index.$_format");
	}

	public function listByAlphaAction($by, $letter, $page, $_format) {
		$request = $this->get('request')->query;
		$country = $request->get('country', '');
		$limit = 100;

		$repo = $this->getPersonRepository();
		$filters = array(
			'by'      => $by,
			'prefix'  => $letter,
			'country' => $country,
		);
		$this->view = array(
			'by'      => $by,
			'letter'  => $letter,
			'country' => $country,
			'persons' => $repo->getBy($filters, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countBy($filters)
			)),
			'route' => $this->getCurrentRoute(),
			'route_params' => array('letter' => $letter, 'by' => $by),
		);

		return $this->display("list_by_alpha.$_format");
	}

	public function showAction($slug, $_format) {
		$person = $this->tryToFindPerson($slug);
		if ( ! $person instanceof Person) {
			return $person;
		}

		$this->prepareViewForShow($person, $_format);
		$this->view['person'] = $person;
		$this->putPersonInfoInView($person);

		return $this->display("show.$_format");
	}

	public function showInfoAction($slug, $_format) {
		$person = $this->tryToFindPerson($slug);
		if ( ! $person instanceof Person) {
			return $person;
		}

		$this->view = array(
			'person' => $person,
		);

		return $this->display("show_info.$_format");
	}

	protected function tryToFindPerson($slug) {
		$person = $this->getPersonRepository()->findBySlug(String::slugify($slug));
		if ($person) {
			return $person;
		}

		$person = $this->getPersonRepository()->findOneBy(array('name' => $slug));
		if ($person) {
			return $this->urlRedirect($this->generateUrl('person_show', array('slug' => $person->getSlug())), true);
		}
		throw $this->createNotFoundException("Няма личност с код $slug.");
	}

	protected function prepareViewForShow(Person $person, $format) {
		$this->prepareViewForShowAuthor($person, $format);
		$this->prepareViewForShowTranslator($person, $format);
	}
	protected function prepareViewForShowAuthor(Person $person, $format) {
		$groupBySeries = $format == 'html';
		$this->view['texts_as_author'] = $this->getTextRepository()->findByAuthor($person, $groupBySeries);
		$this->view['books'] = $this->getBookRepository()->getByAuthor($person);
	}
	protected function prepareViewForShowTranslator(Person $person, $format) {
		$this->view['texts_as_translator'] = $this->getTextRepository()->findByTranslator($person);
	}

	protected function putPersonInfoInView(Person $person) {
		if ($person->getInfo() != '') {
			// TODO move this in the entity
			list($prefix, $name) = explode(':', $person->getInfo(), 2);
			$site = $this->getWikiSiteRepository()->findOneBy(array('code' => $prefix));
			$url = $site->getUrl($name);
			$this->view['info'] = Legacy::getMwContent($url, $this->container->get('buzz'));
			$this->view['info_intro'] = strtr($site->getIntro(), array(
				'$1' => $person->getName(),
				'$2' => $url,
			));
		}
	}

	public function suggest($slug) {
		return $this->legacyPage('Info');
	}

}
