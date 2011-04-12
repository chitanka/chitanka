<?php

namespace Chitanka\LibBundle\Controller;

use Chitanka\LibBundle\Pagination\Pager;

class SeriesController extends Controller
{
	public function indexAction()
	{
		return $this->display('index');
	}

	public function listAction($letter, $page)
	{
		$page = (int)$page;
		$repo = $this->getRepository('Series');
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
			'route' => 'series_by_letter',
			'route_params' => array('letter' => $letter),
		);

		return $this->display('list');
	}


	public function showAction($slug)
	{
		$series = $this->getRepository('Series')->findBySlug($slug);

		$this->view = array(
			'series' => $series,
			'texts'  => $this->getRepository('Text')->getBySeries($series),
		);

		return $this->display('show');
	}

}
