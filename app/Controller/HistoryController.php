<?php namespace App\Controller;

use App\Entity\RevisionRepository;
use App\Pagination\Pager;
use App\Util\Date;

class HistoryController extends Controller {

	protected $responseAge = 3600; // 1 hour

	const PAGE_COUNT_DEFAULT = 50;
	const PAGE_COUNT_LIMIT = 500;

	public function indexAction() {
		return [
			'book_revisions_by_date' => $this->em()->getBookRevisionRepository()->getLatest(static::PAGE_COUNT_DEFAULT),
			'text_revisions_by_date' => $this->em()->getTextRevisionRepository()->getLatest(static::PAGE_COUNT_DEFAULT),
			'_cache' => $this->responseAge,
		];
	}

	public function listBooksAction($page, $_format) {
		$repo = $this->em()->getBookRevisionRepository();
		$pager = new Pager($page, static::PAGE_COUNT_DEFAULT * 50, static::PAGE_COUNT_DEFAULT);
		switch ($_format) {
			case 'html':
			case 'rss':
				$revisions = $repo->getLatest(static::PAGE_COUNT_DEFAULT, $page);
				$lastOnes = current($revisions);
				return [
					'dates' => $this->getDateOptions($repo),
					'book_revisions_by_date' => $revisions,
					'pager' => $pager,
					'last_date' => $lastOnes[0]->getDate(),
					'_cache' => $this->responseAge,
				];
			case 'opds':
			case 'json':
				return [
					'book_revisions' => $repo->getByDate(null, $page, static::PAGE_COUNT_DEFAULT, false),
					'pager' => $pager,
					'_cache' => $this->responseAge,
				];
		}
	}

	public function listBooksByMonthAction($year, $month, $page) {
		$dates = ["$year-$month-01", Date::endOfMonth("$year-$month")];
		$repo = $this->em()->getBookRevisionRepository();

		return [
			'dates' => $this->getDateOptions($repo),
			'month' => ltrim($month, '0'),
			'year' => $year,
			'book_revisions_by_date' => $repo->getByDate($dates, $page, static::PAGE_COUNT_DEFAULT),
			'pager' => new Pager($page, $repo->countByDate($dates), static::PAGE_COUNT_DEFAULT),
			'route_params' => compact('year', 'month'),
		];
	}

	public function listTextsAction($page, $_format) {
		$repo = $this->em()->getTextRevisionRepository();
		$pager = new Pager($page, static::PAGE_COUNT_DEFAULT * 50, static::PAGE_COUNT_DEFAULT);
		switch ($_format) {
			case 'html':
			case 'rss':
				$revisions = $repo->getLatest(static::PAGE_COUNT_DEFAULT, $page);
				$lastOnes = current($revisions);
				return [
					'dates' => $this->getDateOptions($repo),
					'text_revisions_by_date' => $revisions,
					'pager' => $pager,
					'last_date' => $lastOnes[0]->getDate(),
					'_cache' => $this->responseAge,
				];
			case 'opds':
			case 'json':
				return [
					'text_revisions' => $repo->getByDate(null, $page, static::PAGE_COUNT_DEFAULT, false),
					'pager' => $pager,
					'_cache' => $this->responseAge,
				];
		}
	}

	public function listTextsByMonthAction($year, $month, $page) {
		$dates = ["$year-$month-01", Date::endOfMonth("$year-$month")];
		$repo = $this->em()->getTextRevisionRepository();
		$revisions = $repo->getByDate($dates, $page, static::PAGE_COUNT_DEFAULT);

		return [
			'dates' => $this->getDateOptions($repo),
			'month' => ltrim($month, '0'),
			'year' => $year,
			'text_revisions_by_date' => $revisions,
			'texts_by_id' => $this->extractTextsFromRevisionsByDate($revisions),
			'pager'    => new Pager($page, $repo->countByDate($dates), static::PAGE_COUNT_DEFAULT),
			'route' => 'new_texts_by_month',
			'route_params' => compact('year', 'month'),
		];
	}

	private function getDateOptions(RevisionRepository $repository) {
		$dates = [];
		foreach ($repository->getMonths() as $data) {
			$ym = $data['month'];
			list($y, $m) = explode('-', $ym);
			$data['year'] = $y;
			$data['month'] = ltrim($m, '0');
			$dates[$ym] = $data;
		}
		krsort($dates);

		return $dates;
	}

	private function extractTextsFromRevisionsByDate($revisionsByDate) {
		$texts = [];
		foreach ($revisionsByDate as $revisions) {
			foreach ($revisions as $revision) {
				$texts[$revision->getText()->getId()] = $revision->getText();
			}
		}

		return $texts;
	}
}
