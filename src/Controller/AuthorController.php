<?php namespace App\Controller;

use App\Entity\Person;
use App\Pagination\Pager;
use App\Persistence\BookRepository;
use App\Persistence\PersonRepository;
use App\Persistence\TextRepository;
use App\Persistence\UserRepository;
use App\Service\SearchService;
use Symfony\Component\HttpFoundation\Request;

class AuthorController extends PersonController {

	public function __construct(UserRepository $userRepository, PersonRepository $personRepository) {
		parent::__construct($userRepository, $personRepository->asAuthor());
	}

	public function showBooksAction(BookRepository $bookRepository, $slug) {
		$person = $this->tryToFindPerson($slug);
		if ( ! $person instanceof Person) {
			return $person;
		}

		return [
			'person' => $person,
			'books'  => $bookRepository->findByAuthor($person),
		];
	}

	public function showTextsAction(TextRepository $textRepository, $slug, $_format) {
		$person = $this->tryToFindPerson($slug);
		if ( ! $person instanceof Person) {
			return $person;
		}

		$groupBySeries = $_format == 'html';
		return [
			'person' => $person,
			'texts'  => $textRepository->findByAuthor($person, $groupBySeries),
		];
	}

	public function searchAction(SearchService $searchService, Request $request, $_format) {
		$query = $request->query->get('q');
		if ($_format == 'json') {
			$persons = $this->findByQuery([
				'text'  => $query,
				'by'    => 'name,origName',
				'limit' => static::PAGE_COUNT_LIMIT,
			]);
			return $persons;
		}
		if ($_format == 'suggest') {
			$persons = $this->findByQuery([
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => static::PAGE_COUNT_LIMIT,
			]);
			$items = $descs = $urls = [];
			foreach ($persons as $person) {
				$items[] = $person->getName();
				$descs[] = '';
				$urls[] = $this->generateAbsoluteUrl('author_show', ['slug' => $person->getSlug()]);
			}

			return [$query, $items, $descs, $urls];
		}
		return [];
	}

	protected function getShowTemplateParams(TextRepository $textRepository, BookRepository $bookRepository, Person $person, $format) {
		return $this->getShowTemplateParamsAuthor($textRepository, $bookRepository, $person, $format);
	}
}
