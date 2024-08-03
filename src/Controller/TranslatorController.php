<?php namespace App\Controller;

use App\Entity\Person;
use App\Persistence\BookRepository;
use App\Persistence\PersonRepository;
use App\Persistence\TextRepository;
use App\Persistence\UserRepository;
use App\Service\SearchService;
use Symfony\Component\HttpFoundation\Request;

class TranslatorController extends PersonController {

	public function __construct(UserRepository $userRepository, PersonRepository $personRepository) {
		parent::__construct($userRepository, $personRepository->asTranslator());
	}

	public function searchAction(SearchService $searchService, Request $request, $_format) {
		if ($_format == 'suggest') {
			$query = $request->query->get('q');
			$persons = $this->findByQuery([
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => self::PAGE_COUNT_LIMIT,
			]);
			$items = $descs = $urls = [];
			foreach ($persons as $person) {
				$items[] = $person->getName();
				$descs[] = '';
				$urls[] = $this->generateAbsoluteUrl('translator_show', ['slug' => $person->getSlug()]);
			}
			return [$query, $items, $descs, $urls];
		}
		return [];
	}

	protected function getShowTemplateParams(TextRepository $textRepository, BookRepository $bookRepository, Person $person, $format) {
		return $this->getShowTemplateParamsTranslator($textRepository, $person, $format);
	}

}
