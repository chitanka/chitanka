<?php namespace App\Service;

use App\Persistence\BookRepository;
use App\Persistence\CategoryRepository;
use App\Entity\SearchString;
use App\Persistence\LabelRepository;
use App\Persistence\PersonRepository;
use App\Persistence\SearchStringRepository;
use App\Persistence\SequenceRepository;
use App\Persistence\SeriesRepository;
use App\Persistence\TextRepository;
use App\Util\Stringy;
use Symfony\Component\HttpFoundation\Request;

class SearchService {

	const MAX_RESULTS = 100;
	const MAX_LATEST_STRINGS = 30;
	const MAX_TOP_STRINGS = 30;

	const HTTP_STATUS_INVALID = 400;

	private static $minQueryLength = 3;
	private static $maxQueryLength = 60;

	private $searchStringRepository;
	private $personRepository;
	private $textRepository;
	private $bookRepository;
	private $seriesRepository;
	private $sequenceRepository;
	private $labelRepository;
	private $categoryRepository;

	public function __construct(
		SearchStringRepository $searchStringRepository,
		PersonRepository $personRepository,
		TextRepository $textRepository,
		BookRepository $bookRepository,
		SeriesRepository $seriesRepository,
		SequenceRepository $sequenceRepository,
		LabelRepository $labelRepository,
		CategoryRepository $categoryRepository
	) {
		$this->searchStringRepository = $searchStringRepository;
		$this->personRepository = $personRepository;
		$this->textRepository = $textRepository;
		$this->bookRepository = $bookRepository;
		$this->seriesRepository = $seriesRepository;
		$this->sequenceRepository = $sequenceRepository;
		$this->labelRepository = $labelRepository;
		$this->categoryRepository = $categoryRepository;
	}

	public function prepareQuery(Request $request, $format = 'html') {
		$params = $request->query;
		$query = trim($params->get('q'));

		if (empty($query)) {
			return [
				'_template' => "Search/list_top_strings.$format.twig",
				'latest_strings' => $this->searchStringRepository->getLatest(self::MAX_LATEST_STRINGS),
				'top_strings' => $this->searchStringRepository->getTop(self::MAX_TOP_STRINGS),
			];
		}

		$query = Stringy::fixEncoding($query);

		$matchType = $params->get('match');
		if ($matchType != 'exact') {
			try {
				$this->validateQueryLength($query);
			} catch (\InvalidArgumentException $e) {
				return [
					'_template' => "Search/message.$format.twig",
					'_status' => self::HTTP_STATUS_INVALID,
					'message' => $e->getMessage(),
				];
			}
		}

		return [
			'text'  => $query,
			'by'    => $request->get('by'),
			'match' => $matchType,
		];
	}

	/**
	 * @param array $query
	 * @return SearchResult
	 */
	public function executeSearch(array $query) {
		$result = new SearchResult([
			'persons'      => $this->personRepository->getByNames($query['text'], self::MAX_RESULTS),
			'texts'        => $this->textRepository->getByTitles($query['text'], self::MAX_RESULTS),
			'books'        => $this->bookRepository->findByTitleOrIsbn($query['text'], self::MAX_RESULTS),
			'series'       => $this->seriesRepository->getByNames($query['text'], self::MAX_RESULTS),
			'sequences'    => $this->sequenceRepository->getByNames($query['text'], self::MAX_RESULTS),
			'labels'       => $this->labelRepository->getByNames($query['text']),
			'categories'   => $this->categoryRepository->getByNames($query['text']),
		]);
		if (!$result->isEmpty()) {
			$this->logSearch($query['text']);
		}
		return $result;
	}

	/**
	 * @param string $query
	 */
	private function validateQueryLength($query) {
		$queryLength = mb_strlen($query, 'utf-8');
		if ($queryLength < self::$minQueryLength) {
			throw new \InvalidArgumentException(sprintf('Трябва да въведете поне %d знака.', self::$minQueryLength));
		}
		if ($queryLength > self::$maxQueryLength) {
			throw new \InvalidArgumentException(sprintf('Не може да въвеждате повече от %d знака.', self::$maxQueryLength));
		}
	}

	/**
	 * @param string $query
	 */
	private function logSearch($query) {
		$searchString = $this->searchStringRepository->findByName($query);
		if ( ! $searchString) {
			$searchString = new SearchString($query);
		}
		$searchString->incCount();
		$this->searchStringRepository->save($searchString);
	}
}

class SearchResult {

	public $nbResults;

	public function __construct(array $lists) {
		$this->nbResults = array_sum(array_map('count', $lists));
		foreach ($lists as $entity => $list) {
			$this->$entity = $list;
		}
	}

	public function isEmpty() {
		return $this->nbResults == 0;
	}

}
