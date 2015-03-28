<?php namespace App\Controller;

use App\Pagination\Pager;
use App\Util\String;
use App\Service\SearchService;
use Symfony\Component\HttpFoundation\Request;

class SeriesController extends Controller {

	public function indexAction() {
		return [];
	}

	public function listByAlphaAction($letter, $page) {
		$repo = $this->em()->getSeriesRepository();
		$limit = 50;

		$prefix = $letter == '-' ? null : $letter;
		return [
			'letter' => $letter,
			'series' => $repo->getByPrefix($prefix, $page, $limit),
			'pager'    => new Pager([
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countByPrefix($prefix)
			]),
			'route_params' => ['letter' => $letter],
		];
	}

	public function showAction($slug) {
		$slug = String::slugify($slug);
		$series = $this->em()->getSeriesRepository()->findBySlug($slug);
		if ($series === null) {
			throw $this->createNotFoundException("Няма серия с код $slug.");
		}

		return [
			'series' => $series,
			'texts'  => $this->em()->getTextRepository()->getBySeries($series),
		];
	}

	public function searchAction(Request $request, $_format) {
		if ($_format == 'osd') {
			return [];
		}
		if ($_format == 'suggest') {
			$items = $descs = $urls = [];
			$query = $request->query->get('q');
			$series = $this->em()->getSeriesRepository()->getByQuery([
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => 10,
			]);
			foreach ($series as $serie) {
				$items[] = $serie['name'];
				$descs[] = '';
				$urls[] = $this->generateUrl('series_show', ['slug' => $serie['slug']], true);
			}

			return $this->asJson([$query, $items, $descs, $urls]);
		}
		$searchService = new SearchService($this->em());
		$query = $searchService->prepareQuery($request, $_format);
		if (isset($query['_template'])) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'name,orig_name';
		}
		$series = $this->em()->getSeriesRepository()->getByQuery($query);
		$found = count($series) > 0;
		return [
			'query'  => $query,
			'series' => $series,
			'found'  => $found,
			'_status' => !$found ? 404 : null,
		];
	}
}
