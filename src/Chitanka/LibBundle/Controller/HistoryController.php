<?php

namespace Chitanka\LibBundle\Controller;

use Chitanka\LibBundle\Pagination\Pager;
use Chitanka\LibBundle\Util\Datetime;

class HistoryController extends Controller
{

	public function indexAction()
	{
		$this->view = array(
			'book_revisions_by_date' => $this->getRepository('BookRevision')->getLatest(10),
			'text_revisions_by_date' => $this->getRepository('TextRevision')->getLatest(20),
		);


		return $this->display('index');
	}

	public function listBooksAction($_format)
	{
		$this->responseFormat = $_format;
		$repo = $this->getRepository('BookRevision');
		$revisions = $repo->getLatest(20);
		$lastOnes = current($revisions);
		$this->view = array(
			'dates' => $this->getDateOptions($repo),
			'book_revisions_by_date' => $revisions,
			'last_date' => $lastOnes[0]['date'],
		);

		return $this->display('list_books');
	}

	public function listBooksByMonthAction($year, $month, $page)
	{
		$dates = array("$year-$month-01", Datetime::endOfMonth("$year-$month"));
		$limit = 30;

		$repo = $this->getRepository('BookRevision');
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
			'route' => 'new_books_by_month',
			'route_params' => compact('year', 'month'),
		);

		return $this->display('list_books_by_month');
	}

	public function listTextsAction($_format)
	{
		$this->responseFormat = $_format;
		$repo = $this->getRepository('TextRevision');
		$revisions = $repo->getLatest(40);
		$lastOnes = current($revisions);
		$this->view = array(
			'dates' => $this->getDateOptions($repo),
			'text_revisions_by_date' => $revisions,
			'last_date' => $lastOnes[0]['date'],
		);

		return $this->display('list_texts');
	}

	public function listTextsByMonthAction($year, $month, $page)
	{
		$dates = array("$year-$month-01", Datetime::endOfMonth("$year-$month"));
		$limit = 60;

		$repo = $this->getRepository('TextRevision');
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
