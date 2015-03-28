<?php namespace App\Service;

use App\Entity\EntityManager;
use App\Entity\SearchString;
use App\Util\String;
use Symfony\Component\HttpFoundation\Request;

class SearchService {

	const MAX_RESULTS = 50;
	const MAX_LATEST_STRINGS = 30;
	const MAX_TOP_STRINGS = 30;

	const HTTP_STATUS_INVALID = 400;

	private static $minQueryLength = 3;
	private static $maxQueryLength = 60;

	private $em;

	public function __construct(EntityManager $em) {
		$this->em = $em;
	}

	public function prepareQuery(Request $request, $format = 'html') {
		$params = $request->query;
		$query = trim($params->get('q'));

		if (empty($query)) {
			return [
				'_template' => "App:Search:list_top_strings.$format.twig",
				'latest_strings' => $this->em->getSearchStringRepository()->getLatest(self::MAX_LATEST_STRINGS),
				'top_strings' => $this->em->getSearchStringRepository()->getTop(self::MAX_TOP_STRINGS),
			];
		}

		$query = String::fixEncoding($query);

		$matchType = $params->get('match');
		if ($matchType != 'exact') {
			try {
				$this->validateQueryLength($query);
			} catch (\InvalidArgumentException $e) {
				return [
					'_template' => "App:Search:message.$format.twig",
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
			'persons'      => $this->em->getPersonRepository()->getByNames($query['text'], self::MAX_RESULTS),
			'texts'        => $this->em->getTextRepository()->getByTitles($query['text'], self::MAX_RESULTS),
			'books'        => $this->em->getBookRepository()->getByTitleOrIsbn($query['text'], self::MAX_RESULTS),
			'series'       => $this->em->getSeriesRepository()->getByNames($query['text'], self::MAX_RESULTS),
			'sequences'    => $this->em->getSequenceRepository()->getByNames($query['text'], self::MAX_RESULTS),
			'work_entries' => $this->em->getWorkEntryRepository()->getByTitleOrAuthor($query['text']),
			'labels'       => $this->em->getLabelRepository()->getByNames($query['text']),
			'categories'   => $this->em->getCategoryRepository()->getByNames($query['text']),
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
		$searchString = $this->em->getSearchStringRepository()->findByName($query);
		if ( ! $searchString) {
			$searchString = new SearchString($query);
		}
		$searchString->incCount();
		$this->em->getSearchStringRepository()->save($searchString);
	}
}

class SearchResult {

	public $nbResults;

	public function __construct(array $lists) {
		$this->nbResults = array_sum(array_map('count', $lists)) > 0;
		foreach ($lists as $entity => $list) {
			$this->$entity = $list;
		}
	}

	public function isEmpty() {
		return $this->nbResults == 0;
	}

}
