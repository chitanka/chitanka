<?php namespace App\Controller;

use App\Pagination\Pager;
use App\Util\String;

class SeriesController extends Controller {

	public function indexAction($_format) {
		return $this->display("index.$_format");
	}

	public function listByAlphaAction($letter, $page, $_format) {
		$repo = $this->getSeriesRepository();
		$limit = 50;

		$prefix = $letter == '-' ? null : $letter;
		$this->view = array(
			'letter' => $letter,
			'series' => $repo->getByPrefix($prefix, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countByPrefix($prefix)
			)),
			'route_params' => array('letter' => $letter),
		);

		return $this->display("list_by_alpha.$_format");
	}

	public function showAction($slug, $_format) {
		$slug = String::slugify($slug);
		$series = $this->getSeriesRepository()->findBySlug($slug);
		if ($series === null) {
			return $this->notFound("Няма серия с код $slug.");
		}

		$this->view = array(
			'series' => $series,
			'texts'  => $this->getTextRepository()->getBySeries($series),
		);

		return $this->display("show.$_format");
	}

}
