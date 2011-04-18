<?php

namespace Chitanka\LibBundle\Controller;

use Chitanka\LibBundle\Pagination\Pager;
use Chitanka\LibBundle\Entity\SearchString;

class SearchController extends Controller
{
	private $minQueryLength = 4;

	public function indexAction()
	{
		$query = $this->get('request')->query->get('q');

		if ( ! $query) {
			$this->view['strings'] = $this->getRepository('SearchString')->getLatest(30);

			return $this->display('list_top_strings');
		}

		if (mb_strlen($query, 'utf-8') < $this->minQueryLength) {
			$this->view['message'] = sprintf('Трябва да въведете поне %d знака.', $this->minQueryLength);

			return $this->display('message');
		}

		$persons = $this->getRepository('Person')->getByNames($query);
		$texts = $this->getRepository('Text')->getByTitles($query);
		$books = $this->getRepository('Book')->getByTitles($query);
		$series = $this->getRepository('Series')->getByNames($query);
		$sequences = $this->getRepository('Sequence')->getByNames($query);
		$work_entries = $this->getRepository('WorkEntry')->getByTitleOrAuthor($query);

		$found = count($persons) > 0 || count($texts) > 0 || count($books) > 0 || count($series) > 0 || count($sequences) > 0 || count($work_entries) > 0;

		if ($found) {
			$this->logSearch($query);
		}

		$this->view = compact('query', 'persons', 'texts', 'books', 'series', 'sequences', 'work_entries', 'found');

		$response = $this->display('index');

		if ( ! $found) {
			$response->setStatusCode(404);
		}

		return $response;
	}


	private function logSearch($query)
	{
		$searchString = $this->getRepository('SearchString')->findOneBy(array('name' => $query));
		if ( ! $searchString) {
			$searchString = new SearchString($query);
		}
		$searchString->incCount();
		$this->getEntityManager()->persist($searchString);
		$this->getEntityManager()->flush();
	}


	public function latestAction($limit = 10)
	{
		$this->view = array(
			'strings' => $this->getRepository('SearchString')->getLatest($limit),
		);

		return $this->display('top_strings');
	}
}
