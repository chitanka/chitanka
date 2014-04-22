<?php

namespace App\Controller;

use App\Pagination\Pager;
use App\Util\Datetime;

class HistoryController extends Controller
{

	public $booksPerPage = 30;
	public $textsPerPage = 30;

	public function indexAction()
	{
		$this->view = array(
			'book_revisions_by_date' => $this->getBookRevisionRepository()->getLatest($this->booksPerPage),
			'text_revisions_by_date' => $this->getTextRevisionRepository()->getLatest($this->textsPerPage),
		);

		return $this->display('index');
	}

	public function listBooksAction($page, $_format)
	{
		$maxPerPage = $this->booksPerPage;
		$repo = $this->getBookRevisionRepository();
		switch ($_format) {
			case 'html':
			case 'rss':
				$revisions = $repo->getLatest($maxPerPage, $page);
				$lastOnes = current($revisions);
				$this->view = array(
					'dates' => $this->getDateOptions($repo),
					'book_revisions_by_date' => $revisions,
					'last_date' => $lastOnes[0]['date'],
				);
				break;
			case 'opds':
				$this->view = array(
					'book_revisions' => $repo->getByDate(null, $page, $maxPerPage, false),
					'pager'    => new Pager(array(
						'page'  => $page,
						'limit' => $maxPerPage,
						'total' => $maxPerPage * 50
					)),
				);
				break;
		}

		return $this->display("list_books.$_format");
	}

	public function listBooksByMonthAction($year, $month, $page)
	{
		$dates = array("$year-$month-01", Datetime::endOfMonth("$year-$month"));
		$limit = $this->booksPerPage;

		$repo = $this->getBookRevisionRepository();
		$this->view = array(
			'dates' => $this->getDateOptions($repo),
			'month' => ltrim($month, '0'),
			'year' => $year,
			'book_revisions_by_date' => $repo->getByDate($dates, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countByDate($dates)
			)),
			'route_params' => compact('year', 'month'),
		);

		return $this->display("list_books_by_month");
	}

	public function listTextsAction($page, $_format)
	{
		$maxPerPage = $this->textsPerPage;
		$repo = $this->getTextRevisionRepository();
		switch ($_format) {
			case 'html':
			case 'rss':
				$revisions = $repo->getLatest($maxPerPage, $page);
				$lastOnes = current($revisions);
				$this->view = array(
					'dates' => $this->getDateOptions($repo),
					'text_revisions_by_date' => $revisions,
					'last_date' => $lastOnes[0]['date'],
				);
				break;
			case 'opds':
				$this->view = array(
					'text_revisions' => $repo->getByDate(null, $page, $maxPerPage, false),
					'pager'    => new Pager(array(
						'page'  => $page,
						'limit' => $maxPerPage,
						'total' => $maxPerPage * 50
					)),
				);
				break;
		}

		return $this->display("list_texts.$_format");
	}

	public function listTextsByMonthAction($year, $month, $page)
	{
		$dates = array("$year-$month-01", Datetime::endOfMonth("$year-$month"));
		$limit = $this->textsPerPage;

		$repo = $this->getTextRevisionRepository();
		$revisions = $repo->getByDate($dates, $page, $limit);
		$this->view = array(
			'dates' => $this->getDateOptions($repo),
			'month' => ltrim($month, '0'),
			'year' => $year,
			'text_revisions_by_date' => $revisions,
			'texts_by_id' => $this->extractTextsFromRevisionsByDate($revisions),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countByDate($dates)
			)),
			'route' => 'new_texts_by_month',
			'route_params' => compact('year', 'month'),
		);

		return $this->display('list_texts_by_month');
	}


	private function getDateOptions($repository)
	{
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


	private function extractTextsFromRevisionsByDate($revisionsByDate)
	{
		$texts = array();
		foreach ($revisionsByDate as $revisions) {
			foreach ($revisions as $revision) {
				$texts[$revision['text']['id']] = $revision['text'];
			}
		}

		return $texts;
	}
}
