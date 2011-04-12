<?php

namespace Chitanka\LibBundle\Controller;

use Chitanka\LibBundle\Pagination\Pager;

class SequenceController extends Controller
{
	public function indexAction()
	{
		return $this->display('index');
	}

	public function listAction($letter, $page)
	{
		$page = (int)$page;
		$repo = $this->getRepository('Sequence');
		$limit = 50;

		$prefix = $letter == '-' ? null : $letter;
		$this->view = array(
			'letter' => $letter,
			'sequences' => $repo->getByPrefix($prefix, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countByPrefix($prefix)
			)),
			'route' => 'sequences_by_letter',
			'route_params' => array('letter' => $letter),
		);

		return $this->display('list');
	}


	public function showAction($slug)
	{
		$sequence = $this->getRepository('Sequence')->findBySlug($slug);

		$this->view = array(
			'sequence' => $sequence,
			'books'  => $this->getRepository('Book')->getBySequence($sequence),
		);

		return $this->display('show');
	}

}
