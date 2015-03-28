<?php namespace App\Controller;

use App\Pagination\Pager;
use App\Util\String;
use App\Service\SearchService;
use Symfony\Component\HttpFoundation\Request;

class SequenceController extends Controller {

	public function indexAction() {
		return [];
	}

	public function listByAlphaAction($letter, $page) {
		$repo = $this->em()->getSequenceRepository();
		$limit = 50;

		$prefix = $letter == '-' ? null : $letter;
		return [
			'letter' => $letter,
			'sequences' => $repo->getByPrefix($prefix, $page, $limit),
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
		$sequence = $this->em()->getSequenceRepository()->findBySlug($slug);
		if ($sequence === null) {
			throw $this->createNotFoundException("Няма поредица с код $slug.");
		}
		return [
			'sequence' => $sequence,
			'books'  => $this->em()->getBookRepository()->getBySequence($sequence),
		];
	}

	public function searchAction(Request $request, $_format) {
		if ($_format == 'osd') {
			return [];
		}
		if ($_format == 'suggest') {
			$items = $descs = $urls = [];
			$query = $request->query->get('q');
			$sequences = $this->em()->getSequenceRepository()->getByQuery([
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => 10,
			]);
			foreach ($sequences as $sequence) {
				$items[] = $sequence['name'];
				$descs[] = '';
				$urls[] = $this->generateUrl('sequence_show', ['slug' => $sequence['slug']], true);
			}

			return $this->asJson([$query, $items, $descs, $urls]);
		}
		$searchService = new SearchService($this->em());
		$query = $searchService->prepareQuery($request, $_format);
		if (isset($query['_template'])) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'name';
		}
		$sequences = $this->em()->getSequenceRepository()->getByQuery($query);
		$found = count($sequences) > 0;
		return [
			'query'     => $query,
			'sequences' => $sequences,
			'found'     => $found,
			'_status' => !$found ? 404 : null,
		];
	}
}
