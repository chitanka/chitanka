<?php namespace App\Controller;

use App\Entity\SearchString;
use App\Service\SearchService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller {

	public function indexAction(Request $request, $_format) {
		if ($_format == 'osd') {
			return array();
		}
		$searchService = new SearchService($this->em(), $this->get('templating'));
		if (($query = $searchService->prepareQuery($request, $_format)) instanceof Response) {
			return $query;
		}

		$lists = array(
			'persons'      => $this->em()->getPersonRepository()->getByNames($query['text'], 15),
			'texts'        => $this->em()->getTextRepository()->getByTitles($query['text'], 15),
			'books'        => $this->em()->getBookRepository()->getByTitles($query['text'], 15),
			'series'       => $this->em()->getSeriesRepository()->getByNames($query['text'], 15),
			'sequences'    => $this->em()->getSequenceRepository()->getByNames($query['text'], 15),
			'work_entries' => $this->em()->getWorkEntryRepository()->getByTitleOrAuthor($query['text']),
			'labels'       => $this->em()->getLabelRepository()->getByNames($query['text']),
			'categories'   => $this->em()->getCategoryRepository()->getByNames($query['text']),
		);

		$found = array_sum(array_map('count', $lists)) > 0;

		if ($found) {
			$this->logSearch($query['text']);
		}

		return array(
			'query' => $query,
			'found' => $found,
			'_status' => !$found ? 404 : null,
		) + $lists;
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
