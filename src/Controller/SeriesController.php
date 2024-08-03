<?php namespace App\Controller;

use App\Pagination\Pager;
use App\Persistence\SeriesRepository;
use App\Persistence\TextRepository;
use App\Service\SearchService;
use App\Util\Stringy;
use Symfony\Component\HttpFoundation\Request;

class SeriesController extends Controller {

	const PAGE_COUNT_DEFAULT = 100;
	const PAGE_COUNT_LIMIT = 1000;

	public function indexAction() {
		return [];
	}

	public function listByAlphaAction(SeriesRepository $repo, Request $request, $letter, $page) {
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);

		$prefix = $letter == '-' ? null : $letter;
		return [
			'letter' => $letter,
			'series' => $repo->getByPrefix($prefix, $page, $limit),
			'pager'    => new Pager($page, $repo->countByPrefix($prefix), $limit),
			'route_params' => ['letter' => $letter],
		];
	}

	public function showAction(SeriesRepository $seriesRepository, TextRepository $textRepository, $slug) {
		$slug = Stringy::slugify($slug);
		$series = $seriesRepository->findBySlug($slug);
		if ($series === null) {
			throw $this->createNotFoundException("Няма серия с код $slug.");
		}

		return [
			'series' => $series,
			'texts'  => $textRepository->getBySeries($series),
		];
	}

	public function searchAction(SearchService $searchService, SeriesRepository $seriesRepository, Request $request, $_format) {
		if ($_format == 'osd') {
			return [];
		}
		if ($_format == 'suggest') {
			$items = $descs = $urls = [];
			$query = $request->query->get('q');
			$series = $seriesRepository->getByQuery([
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => 10,
			]);
			foreach ($series as $serie) {
				$items[] = $serie['name'];
				$descs[] = '';
				$urls[] = $this->generateAbsoluteUrl('series_show', ['slug' => $serie['slug']]);
			}

			return [$query, $items, $descs, $urls];
		}
		$query = $searchService->prepareQuery($request, $_format);
		if (isset($query['_template'])) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'name,origName';
		}
		$series = $seriesRepository->getByQuery($query);
		$found = count($series) > 0;
		return [
			'query'  => $query,
			'series' => $series,
			'found'  => $found,
			'_status' => !$found ? 404 : null,
		];
	}
}
