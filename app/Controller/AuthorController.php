<?php namespace App\Controller;

use App\Pagination\Pager;
use App\Entity\Person;

class AuthorController extends PersonController {
	public function listByCountryIndexAction($by, $_format) {
		$this->view = array(
			'by' => $by,
			'countries' => $this->getPersonRepository()->getCountryList()
		);

		return $this->display("list_by_country_index.$_format");
	}

	public function listByCountryAction($country, $by, $page, $_format) {
		$limit = 100;

		$repo = $this->getPersonRepository();
		$filters = array(
			'by'      => $by,
			'country' => $country,
		);
		$this->view = array(
			'by'      => $by,
			'country' => $country,
			'persons' => $repo->getBy($filters, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countBy($filters)
			)),
			'route' => $this->getCurrentRoute(),
			'route_params' => array('country' => $country, 'by' => $by, '_format' => $_format),
		);

		return $this->display("list_by_country.$_format");
	}

	public function showBooksAction($slug, $_format) {
		$person = $this->tryToFindPerson($slug);
		if ( ! $person instanceof Person) {
			return $person;
		}

		$this->view = array(
			'person' => $person,
			'books'  => $this->em->getBookRepository()->getByAuthor($person),
		);

		return $this->display("show_books.$_format");
	}

	public function showTextsAction($slug, $_format) {
		$person = $this->tryToFindPerson($slug);
		if ( ! $person instanceof Person) {
			return $person;
		}

		$groupBySeries = $_format == 'html';
		$this->view = array(
			'person' => $person,
			'texts'  => $this->em->getTextRepository()->findByAuthor($person, $groupBySeries),
		);

		return $this->display("show_texts.$_format");
	}

	protected function prepareViewForShow(Person $person, $format) {
		$this->prepareViewForShowAuthor($person, $format);
	}

	protected function getPersonRepository() {
		return $this->em->getPersonRepository()->asAuthor();
	}
}
