<?php namespace App\Controller;

use App\Entity\Person;
use App\Pagination\Pager;
use Symfony\Component\HttpFoundation\Request;

class AuthorController extends PersonController {

	public function showBooksAction($slug) {
		$person = $this->tryToFindPerson($slug);
		if ( ! $person instanceof Person) {
			return $person;
		}

		return [
			'person' => $person,
			'books'  => $this->em()->getBookRepository()->findByAuthor($person),
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
		$query = $request->query->get('q');
		if ($_format == 'json') {
			$persons = $this->em()->getPersonRepository()->asAuthor()->findByQuery([
				'text'  => $query,
				'by'    => 'name,orig_name',
				'limit' => static::PAGE_COUNT_LIMIT,
			]);
			return $persons;
		}
		if ($_format == 'suggest') {
			$persons = $this->em()->getPersonRepository()->asAuthor()->findByQuery([
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => static::PAGE_COUNT_LIMIT,
			]);
			$items = $descs = $urls = [];
			foreach ($persons as $person) {
				$items[] = $person->getName();
				$descs[] = '';
				$urls[] = $this->generateUrl('author_show', ['slug' => $person->getSlug()], true);
			}

			return [$query, $items, $descs, $urls];
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
