<?php namespace App\Controller;

use App\Pagination\Pager;
use App\Util\String;
use App\Service\SearchService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SeriesController extends Controller {

	public function indexAction() {
		return array();
	}

	public function listByAlphaAction($letter, $page) {
		$repo = $this->em()->getSeriesRepository();
		$limit = 50;

		$prefix = $letter == '-' ? null : $letter;
		return array(
			'letter' => $letter,
			'series' => $repo->getByPrefix($prefix, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countByPrefix($prefix)
			)),
			'route_params' => array('letter' => $letter),
		);
	}

	public function showAction($slug) {
		$slug = String::slugify($slug);
		$series = $this->em()->getSeriesRepository()->findBySlug($slug);
		if ($series === null) {
			throw $this->createNotFoundException("Няма серия с код $slug.");
		}

		return array(
			'series' => $series,
			'texts'  => $this->em()->getTextRepository()->getBySeries($series),
		);
	}

	public function searchAction(Request $request, $_format) {
		if ($_format == 'osd') {
			return array();
		}
		if ($_format == 'suggest') {
			$items = $descs = $urls = array();
			$query = $request->query->get('q');
			$series = $this->em()->getSeriesRepository()->getByQuery(array(
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => 10,
			));
			foreach ($series as $serie) {
				$items[] = $serie['name'];
				$descs[] = '';
				$urls[] = $this->generateUrl('series_show', array('slug' => $serie['slug']), true);
			}

			return $this->asJson(array($query, $items, $descs, $urls));
		}
		$searchService = new SearchService($this->em(), $this->get('templating'));
		if (($query = $searchService->prepareQuery($request, $_format)) instanceof Response) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'name,orig_name';
		}
		$series = $this->em()->getSeriesRepository()->getByQuery($query);
		$found = count($series) > 0;
		return array(
			'query'  => $query,
			'series' => $series,
			'found'  => $found,
			'_status' => !$found ? 404 : null,
		);
	}
}
