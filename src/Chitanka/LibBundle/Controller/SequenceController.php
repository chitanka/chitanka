<?php
namespace Chitanka\LibBundle\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Chitanka\LibBundle\Pagination\Pager;
use Chitanka\LibBundle\Util\String;

class SequenceController extends Controller
{
	public function indexAction($_format)
	{
		$this->responseFormat = $_format;

		return $this->display('index');
	}

	public function listByAlphaAction($letter, $page, $_format)
	{
		$repo = $this->getSequenceRepository();
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
			'route' => $this->getCurrentRoute(),
			'route_params' => array('letter' => $letter),
		);
		$this->responseFormat = $_format;

		return $this->display('list_by_alpha');
	}


	public function showAction($slug, $_format)
	{
		$slug = String::slugify($slug);
		$sequence = $this->getSequenceRepository()->findBySlug($slug);
		if ($sequence === null) {
			throw new NotFoundHttpException("Няма издателска поредица с код $slug.");
		}

		$this->view = array(
			'sequence' => $sequence,
			'books'  => $this->getBookRepository()->getBySequence($sequence),
		);
		$this->responseFormat = $_format;

		return $this->display('show');
	}

}
