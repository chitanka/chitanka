<?php namespace App\Controller;

use App\Entity\SearchString;
use App\Service\SearchService;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends Controller {

	const MAX_RESULTS = 50;

	public function indexAction(Request $request, $_format) {
		if ($_format == 'osd') {
			return [];
		}
		$searchService = new SearchService($this->em());
		$query = $searchService->prepareQuery($request, $_format);
		if (isset($query['_template'])) {
			return $query;
		}

		$lists = [
			'persons'      => $this->em()->getPersonRepository()->getByNames($query['text'], self::MAX_RESULTS),
			'texts'        => $this->em()->getTextRepository()->getByTitles($query['text'], self::MAX_RESULTS),
			'books'        => $this->em()->getBookRepository()->getByTitleOrIsbn($query['text'], self::MAX_RESULTS),
			'series'       => $this->em()->getSeriesRepository()->getByNames($query['text'], self::MAX_RESULTS),
			'sequences'    => $this->em()->getSequenceRepository()->getByNames($query['text'], self::MAX_RESULTS),
			'work_entries' => $this->em()->getWorkEntryRepository()->getByTitleOrAuthor($query['text']),
			'labels'       => $this->em()->getLabelRepository()->getByNames($query['text']),
			'categories'   => $this->em()->getCategoryRepository()->getByNames($query['text']),
		];

		$found = array_sum(array_map('count', $lists)) > 0;

		if ($found) {
			$this->logSearch($query['text']);
		}

		return [
			'query' => $query,
			'found' => $found,
			'_status' => !$found ? 404 : null,
		] + $lists;
	}

	/**
	 * @param string $query
	 */
	private function logSearch($query) {
		$searchString = $this->em()->getSearchStringRepository()->findByName($query);
		if ( ! $searchString) {
			$searchString = new SearchString($query);
		}
		$searchString->incCount();
		$this->em()->getSearchStringRepository()->save($searchString);
	}

}
