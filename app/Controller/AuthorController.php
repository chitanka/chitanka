<?php namespace App\Controller;

use App\Pagination\Pager;
use App\Entity\Person;
use Symfony\Component\HttpFoundation\Request;

class AuthorController extends PersonController {

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

	public function showBooksAction($slug) {
		$person = $this->tryToFindPerson($slug);
		if ( ! $person instanceof Person) {
			return $person;
		}

		return [
			'person' => $person,
			'books'  => $this->em()->getBookRepository()->getByAuthor($person),
		];
	}

	public function showTextsAction($slug, $_format) {
		$person = $this->tryToFindPerson($slug);
		if ( ! $person instanceof Person) {
			return $person;
		}

		$groupBySeries = $_format == 'html';
		return [
			'person' => $person,
			'texts'  => $this->em()->getTextRepository()->findByAuthor($person, $groupBySeries),
		];
	}

	public function searchAction(Request $request, $_format) {
		if ($_format == 'suggest') {
			$items = $descs = $urls = [];
			$query = $request->query->get('q');
			$persons = $this->em()->getPersonRepository()->asAuthor()->getByQuery([
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => 10,
			]);
			foreach ($persons as $person) {
				$items[] = $person['name'];
				$descs[] = '';
				$urls[] = $this->generateUrl('author_show', ['slug' => $person['slug']], true);
			}

			return $this->asJson([$query, $items, $descs, $urls]);
		}
		return [];
	}

	protected function getShowTemplateParams(Person $person, $format) {
		return $this->getShowTemplateParamsAuthor($person, $format);
	}

	protected function getPersonRepository() {
		return $this->em()->getPersonRepository()->asAuthor();
	}
}
