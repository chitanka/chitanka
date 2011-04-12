<?php

namespace Chitanka\LibBundle\Controller;

use Chitanka\LibBundle\Pagination\Pager;
//use Chitanka\LibBundle\Form\SeriesForm;

class SeriesController extends Controller
{
	protected $repository = 'Series';

	public function indexAction()
	{
		return $this->display('index');
	}

	public function listAction($letter, $page)
	{
		$page = (int)$page;
		$repo = $this->getRepository();
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
		$series = $this->getRepository()->findBySlug($slug);

		$this->view = array(
			'series' => $series,
			'texts'  => $this->getRepository('Text')->getBySeries($series),
		);

		return $this->display('show');
	}


	public function createAction()
	{
	}

	public function editAction($id)
	{
		$label = $this->getRepository()->find($id);
		$form = new SeriesForm('label', $label, $this->get('validator'));
		$form->setEm($this->getEntityManager())->setup();

		$this->view = array(
			'label' => $label,
			'form' => $form,
		);

		if ('POST' === $this->get('request')->getMethod()) {
			$form->bindAndProcess($this->get('request')->request);
		}

		return $this->display('edit');
	}

}
