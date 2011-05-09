<?php

namespace Chitanka\LibBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Chitanka\LibBundle\Pagination\Pager;
use Chitanka\LibBundle\Entity\SearchString;

class SearchController extends Controller
{
	protected $responseAge = 86400; // 24 hours
	private $minQueryLength = 4;

	public function indexAction()
	{
		if (($query = $this->getQuery()) instanceof Response) {
			return $query;
		}

		$persons = $this->getRepository('Person')->getByNames($query);
		$texts = $this->getRepository('Text')->getByTitles($query);
		$books = $this->getRepository('Book')->getByTitles($query);
		$series = $this->getRepository('Series')->getByNames($query);
		$sequences = $this->getRepository('Sequence')->getByNames($query);
		$work_entries = $this->getRepository('WorkEntry')->getByTitleOrAuthor($query);
		$labels = $this->getRepository('Label')->getByNames($query);
		$categories = $this->getRepository('Category')->getByNames($query);

		$found = count($persons) > 0 || count($texts) > 0 || count($books) > 0 || count($series) > 0 || count($sequences) > 0 || count($work_entries) > 0 || count($labels) > 0 || count($categories) > 0;

		if ($found) {
			$this->logSearch($query);
		} else {
			$this->responseStatusCode = 404;
		}

		$this->view = compact('query', 'persons', 'texts', 'books', 'series', 'sequences', 'work_entries', 'labels', 'categories', 'found');

		return $this->display('index');
	}


	public function personsAction($_format)
	{
		if (($query = $this->getQuery($_format)) instanceof Response) {
			return $query;
		}

		$persons = $this->getRepository('Person')->getByNames($query);
		if ( ! ($found = count($persons) > 0)) {
			$this->responseStatusCode = 404;
		}
		$this->view = compact('query', 'persons', 'found');
		$this->responseFormat = $_format;

		return $this->display('persons');
	}


	public function textsAction($_format)
	{
		if (($query = $this->getQuery($_format)) instanceof Response) {
			return $query;
		}

		$texts = $this->getRepository('Text')->getByTitles($query);
		if ( ! ($found = count($texts) > 0)) {
			$this->responseStatusCode = 404;
		}
		$this->view = compact('query', 'texts', 'found');
		$this->responseFormat = $_format;

		return $this->display('texts');
	}


	public function booksAction($_format)
	{
		if (($query = $this->getQuery($_format)) instanceof Response) {
			return $query;
		}

		$books = $this->getRepository('Book')->getByTitles($query);
		if ( ! ($found = count($books) > 0)) {
			$this->responseStatusCode = 404;
		}
		$this->view = compact('query', 'books', 'found');
		$this->responseFormat = $_format;

		return $this->display('books');
	}


	public function seriesAction($_format)
	{
		if (($query = $this->getQuery($_format)) instanceof Response) {
			return $query;
		}

		$series = $this->getRepository('Series')->getByNames($query);
		if ( ! ($found = count($series) > 0)) {
			$this->responseStatusCode = 404;
		}
		$this->view = compact('query', 'series', 'found');
		$this->responseFormat = $_format;

		return $this->display('series');
	}


	public function sequencesAction($_format)
	{
		if (($query = $this->getQuery($_format)) instanceof Response) {
			return $query;
		}

		$sequences = $this->getRepository('Sequence')->getByNames($query);
		if ( ! ($found = count($sequences) > 0)) {
			$this->responseStatusCode = 404;
		}
		$this->view = compact('query', 'sequences', 'found');

		return $this->display('sequences');
	}


	private function getQuery($_format = 'html')
	{
		$this->responseFormat = $_format;
		$query = $this->get('request')->query->get('q');

		if ( ! $query) {
			$this->view['strings'] = $this->getRepository('SearchString')->getLatest(30);

			return $this->display('list_top_strings');
		}

		if (mb_strlen($query, 'utf-8') < $this->minQueryLength) {
			$this->view['message'] = sprintf('Трябва да въведете поне %d знака.', $this->minQueryLength);

			return $this->display('message');
		}

		return $query;
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
		$this->responseAge = 600; // 10 minutes
		$this->view = array(
			'strings' => $this->getRepository('SearchString')->getLatest($limit),
		);

		return $this->display('top_strings');
	}
}
