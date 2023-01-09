<?php namespace App\Controller;

use App\Pagination\Pager;
use App\Persistence\BookRepository;
use App\Persistence\SequenceRepository;
use App\Service\SearchService;
use App\Util\Stringy;
use Symfony\Component\HttpFoundation\Request;

class SequenceController extends Controller {

	const PAGE_COUNT_DEFAULT = 100;
	const PAGE_COUNT_LIMIT = 1000;

	public function indexAction() {
		return [];
	}

	public function listByAlphaAction(SequenceRepository $repo, Request $request, $letter, $page) {
		$limit = min($request->query->get('limit', static::PAGE_COUNT_DEFAULT), static::PAGE_COUNT_LIMIT);

		$prefix = $letter == '-' ? null : $letter;
		return [
			'letter' => $letter,
			'sequences' => $repo->getByPrefix($prefix, $page, $limit),
			'pager'    => new Pager($page, $repo->countByPrefix($prefix), $limit),
			'route_params' => ['letter' => $letter],
		];
	}

	public function showAction(SequenceRepository $sequenceRepository, BookRepository $bookRepository, $slug) {
		$slug = Stringy::slugify($slug);
		$sequence = $sequenceRepository->findBySlug($slug);
		if ($sequence === null) {
			throw $this->createNotFoundException("Няма поредица с код $slug.");
		}
		return [
			'sequence' => $sequence,
			'books'  => $bookRepository->findBySequence($sequence),
		];
	}

	public function searchAction(SearchService $searchService, SequenceRepository $sequenceRepository, Request $request, $_format) {
		if ($_format == 'osd') {
			return [];
		}
		if ($_format == 'suggest') {
			$items = $descs = $urls = [];
			$query = $request->query->get('q');
			$sequences = $sequenceRepository->getByQuery([
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => self::PAGE_COUNT_LIMIT,
			]);
			foreach ($sequences as $sequence) {
				$items[] = $sequence['name'];
				$descs[] = '';
				$urls[] = $this->generateAbsoluteUrl('sequence_show', ['slug' => $sequence['slug']]);
			}

			return [$query, $items, $descs, $urls];
		}
		$query = $searchService->prepareQuery($request, $_format);
		if (isset($query['_template'])) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'name';
		}
		$sequences = $sequenceRepository->getByQuery($query);
		$found = count($sequences) > 0;
		return [
			'query'     => $query,
			'sequences' => $sequences,
			'found'     => $found,
			'_status' => !$found ? 404 : null,
		];
	}
}
