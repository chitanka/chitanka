<?php namespace App\Controller;

use App\Persistence\BookRevisionRepository;
use App\Persistence\RevisionRepository;
use App\Pagination\Pager;
use App\Persistence\TextRevisionRepository;
use App\Util\Date;

class HistoryController extends Controller {

	protected $responseAge = 3600; // 1 hour

	const PAGE_COUNT_DEFAULT = 50;
	const PAGE_COUNT_LIMIT = 500;

	public function indexAction(BookRevisionRepository $bookRevisionRepository, TextRevisionRepository $textRevisionRepository) {
		return [
			'book_revisions_by_date' => $bookRevisionRepository->getLatest(static::PAGE_COUNT_DEFAULT),
			'text_revisions_by_date' => $textRevisionRepository->getLatest(static::PAGE_COUNT_DEFAULT),
			'_cache' => $this->responseAge,
		];
	}

	public function listBooksAction(BookRevisionRepository $repo, $page, $_format) {
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

	public function listBooksByMonthAction(BookRevisionRepository $bookRevisionRepository, $year, $month, $page) {
		$dates = ["$year-$month-01", Date::endOfMonth("$year-$month")];
		return $this->viewParametersForDateSelectionOfBooks($bookRevisionRepository, $dates, $page, $year, $month);
	}

	public function listBooksByDayAction(BookRevisionRepository $bookRevisionRepository, int $year, int $month, int $day, int $page) {
		$date = "$year-$month-$day";
		return $this->viewParametersForDateSelectionOfBooks($bookRevisionRepository, $date, $page, $year, $month, $day);
	}

	private function viewParametersForDateSelectionOfBooks(BookRevisionRepository $repo, $date, int $page, int $year, int $month, int $day = null): array {
		return [
			'dates' => $this->getDateOptions($repo),
			'day' => $day,
			'month' => ltrim($month, '0'),
			'year' => $year,
			'book_revisions_by_date' => $repo->getByDate($date, $page, static::PAGE_COUNT_DEFAULT),
			'pager' => new Pager($page, $repo->countByDate($date), static::PAGE_COUNT_DEFAULT),
			'route_params' => compact('year', 'month', 'day'),
		];
	}

	public function listTextsAction(TextRevisionRepository $repo, $page, $_format) {
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

	public function listTextsByMonthAction(TextRevisionRepository $textRevisionRepository, $year, $month, $page) {
		$dates = ["$year-$month-01", Date::endOfMonth("$year-$month")];
		return $this->viewParametersForDateSelectionOfTexts($textRevisionRepository, $dates, $page, $year, $month);
	}

	public function listTextsByDayAction(TextRevisionRepository $textRevisionRepository, int $year, int $month, int $day, int $page) {
		$date = "$year-$month-$day";
		return $this->viewParametersForDateSelectionOfTexts($textRevisionRepository, $date, $page, $year, $month, $day);
	}

	private function viewParametersForDateSelectionOfTexts(TextRevisionRepository $repo, $date, int $page, int $year, int $month, int $day = null): array {
		$revisions = $repo->getByDate($date, $page, static::PAGE_COUNT_DEFAULT);
		return [
			'dates' => $this->getDateOptions($repo),
			'day' => $day,
			'month' => ltrim($month, '0'),
			'year' => $year,
			'text_revisions_by_date' => $revisions,
			'texts_by_id' => $this->extractTextsFromRevisionsByDate($revisions),
			'pager' => new Pager($page, $repo->countByDate($date), static::PAGE_COUNT_DEFAULT),
			'route_params' => compact('year', 'month', 'day'),
		];
	}

	private function getDateOptions(RevisionRepository $repository) {
		$dates = [];
		foreach ($repository->getMonths() as $data) {
			$ym = $data['month'];
			[$y, $m] = explode('-', $ym);
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
