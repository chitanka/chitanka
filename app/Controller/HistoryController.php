<?php namespace App\Controller;

use App\Pagination\Pager;
use App\Util\Date;

class HistoryController extends Controller {

	protected $responseAge = 3600; // 1 hour

	public $booksPerPage = 30;
	public $textsPerPage = 30;

	public function indexAction() {
		return array(
			'book_revisions_by_date' => $this->em()->getBookRevisionRepository()->getLatest($this->booksPerPage),
			'text_revisions_by_date' => $this->em()->getTextRevisionRepository()->getLatest($this->textsPerPage),
			'_cache' => $this->responseAge,
		);
	}

	public function listBooksAction($page, $_format) {
		$repo = $this->em()->getBookRevisionRepository();
		switch ($_format) {
			case 'html':
			case 'rss':
				$revisions = $repo->getLatest($this->booksPerPage, $page);
				$lastOnes = current($revisions);
				return array(
					'dates' => $this->getDateOptions($repo),
					'book_revisions_by_date' => $revisions,
					'last_date' => $lastOnes[0]['date'],
					'_cache' => $this->responseAge,
				);
			case 'opds':
			case 'json':
				return array(
					'book_revisions' => $repo->getByDate(null, $page, $this->booksPerPage, false),
					'pager' => new Pager(array(
						'page'  => $page,
						'limit' => $this->booksPerPage,
						'total' => $this->booksPerPage * 50
					)),
					'_cache' => $this->responseAge,
				);
		}
	}

	public function listBooksByMonthAction($year, $month, $page) {
		$dates = array("$year-$month-01", Date::endOfMonth("$year-$month"));
		$repo = $this->em()->getBookRevisionRepository();

		return array(
			'dates' => $this->getDateOptions($repo),
			'month' => ltrim($month, '0'),
			'year' => $year,
			'book_revisions_by_date' => $repo->getByDate($dates, $page, $this->booksPerPage),
			'pager' => new Pager(array(
				'page'  => $page,
				'limit' => $this->booksPerPage,
				'total' => $repo->countByDate($dates)
			)),
			'route_params' => compact('year', 'month'),
		);
	}

	public function listTextsAction($page, $_format) {
		$repo = $this->em()->getTextRevisionRepository();
		switch ($_format) {
			case 'html':
			case 'rss':
				$revisions = $repo->getLatest($this->textsPerPage, $page);
				$lastOnes = current($revisions);
				return array(
					'dates' => $this->getDateOptions($repo),
					'text_revisions_by_date' => $revisions,
					'last_date' => $lastOnes[0]['date'],
					'_cache' => $this->responseAge,
				);
			case 'opds':
			case 'json':
				return array(
					'text_revisions' => $repo->getByDate(null, $page, $this->textsPerPage, false),
					'pager'    => new Pager(array(
						'page'  => $page,
						'limit' => $this->textsPerPage,
						'total' => $this->textsPerPage * 50
					)),
					'_cache' => $this->responseAge,
				);
		}
	}

	public function listTextsByMonthAction($year, $month, $page) {
		$dates = array("$year-$month-01", Date::endOfMonth("$year-$month"));
		$repo = $this->em()->getTextRevisionRepository();
		$revisions = $repo->getByDate($dates, $page, $this->textsPerPage);

		return array(
			'dates' => $this->getDateOptions($repo),
			'month' => ltrim($month, '0'),
			'year' => $year,
			'text_revisions_by_date' => $revisions,
			'texts_by_id' => $this->extractTextsFromRevisionsByDate($revisions),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $this->textsPerPage,
				'total' => $repo->countByDate($dates)
			)),
			'route' => 'new_texts_by_month',
			'route_params' => compact('year', 'month'),
		);
	}

	private function getDateOptions($repository) {
		$dates = array();
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
		$texts = array();
		foreach ($revisionsByDate as $revisions) {
			foreach ($revisions as $revision) {
				$texts[$revision['text']['id']] = $revision['text'];
			}
		}

		return $texts;
	}
}
