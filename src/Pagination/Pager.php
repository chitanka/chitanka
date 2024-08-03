<?php namespace App\Pagination;

class Pager implements \JsonSerializable {

	private $page = 1;
	private $total = 0;
	private $limit = 30;
	private $pageCount = 0;

	public function __construct($page, $total, $limit) {
		$this->page = (int) max($page, 1);
		$this->total = (int) max($total, 0);
		$this->limit = (int) max($limit, 1);

		$this->pageCount = (int) ceil($this->total / $this->limit);
		if ($this->page > $this->pageCount) {
			$this->page = $this->pageCount;
		}
	}

	public function page() {
		return $this->page;
	}

	public function count() {
		return $this->pageCount;
	}

	public function total() {
		return $this->total;
	}

	public function show() {
		return $this->pageCount > 1;
	}

	public function has_prev() {
		return $this->page > 1;
	}

	public function prev() {
		return max($this->page - 1, 1);
	}

	public function next() {
		return min($this->page + 1, $this->pageCount);
	}

	public function has_next() {
		return $this->page < $this->pageCount;
	}

	public function pages() {
		$pages = [];
		$first = 1;
		$selected = max($this->page, $first);
		$start = $first;
		$end = min($first + 2, $this->pageCount);
		for ($i = $start; $i <= $end; $i++) {
			$pages[$i] = false;
		}

		$start = max($first, $selected - 2);
		$end = min($selected + 2, $this->pageCount);
		for ($i = $start; $i <= $end; $i++) {
			$pages[$i] = false;
		}

		$start = max($first, $this->pageCount - 2);
		$end = $this->pageCount;
		for ($i = $start; $i <= $end; $i++) {
			$pages[$i] = false;
		}

		$pages[$selected] = true;

		return $pages;
	}

	public function jsonSerialize() {
		return [
			'page' => $this->page,
			'countPerPage' => $this->limit,
			'totalCount' => $this->total,
			'pageCount' => $this->pageCount,
			'prevPage' => $this->has_prev() ? $this->prev() : null,
			'nextPage' => $this->has_next() ? $this->next() : null,
		];
	}

}
