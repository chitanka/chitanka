<?php namespace App\Controller;

use App\Service\SearchService;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends Controller {

	public function indexAction(SearchService $searchService, Request $request, $_format) {
		if ($_format == 'osd') {
			return [];
		}
		$query = $searchService->prepareQuery($request, $_format);
		if (isset($query['_template'])) {
			return $query;
		}

		$result = $searchService->executeSearch($query);

		return [
			'query' => $query,
			'result' => $result,
			'_status' => $result->isEmpty() ? 404 : null,
		];
	}

}
