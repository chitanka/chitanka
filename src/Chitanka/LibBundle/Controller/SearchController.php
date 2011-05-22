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

		$persons = $this->getRepository('Person')->getByNames($query['text']);
		$texts = $this->getRepository('Text')->getByTitles($query['text']);
		$books = $this->getRepository('Book')->getByTitles($query['text']);
		$series = $this->getRepository('Series')->getByNames($query['text']);
		$sequences = $this->getRepository('Sequence')->getByNames($query['text']);
		$work_entries = $this->getRepository('WorkEntry')->getByTitleOrAuthor($query['text']);
		$labels = $this->getRepository('Label')->getByNames($query['text']);
		$categories = $this->getRepository('Category')->getByNames($query['text']);

		$found = count($persons) > 0 || count($texts) > 0 || count($books) > 0 || count($series) > 0 || count($sequences) > 0 || count($work_entries) > 0 || count($labels) > 0 || count($categories) > 0;

		if ($found) {
			$this->logSearch($query['text']);
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

		if (empty($query['by'])) {
			$query['by'] = 'name,orig_name';
		}
		$persons = $this->getRepository('Person')->getByQuery($query);
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

		if (empty($query['by'])) {
			$query['by'] = 'title,subtitle,orig_title';
		}
		$texts = $this->getRepository('Text')->getByQuery($query);
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

		if (empty($query['by'])) {
			$query['by'] = 'title,subtitle,orig_title';
		}
		$books = $this->getRepository('Book')->getByQuery($query);
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

		if (empty($query['by'])) {
			$query['by'] = 'name,orig_name';
		}
		$series = $this->getRepository('Series')->getByQuery($query);
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

		if (empty($query['by'])) {
			$query['by'] = 'name';
		}
		$sequences = $this->getRepository('Sequence')->getByQuery($query);
		if ( ! ($found = count($sequences) > 0)) {
			$this->responseStatusCode = 404;
		}
		$this->view = compact('query', 'sequences', 'found');

		return $this->display('sequences');
	}


	private function getQuery($_format = 'html')
	{
		$this->responseFormat = $_format;
		$request = $this->get('request')->query;
		$query = $request->get('q');

		if ( ! $query) {
			$this->view = array(
				'latest_strings' => $this->getRepository('SearchString')->getLatest(30),
				'top_strings' => $this->getRepository('SearchString')->getTop(30),
			);

			return $this->display('list_top_strings');
		}

		$matchType = $request->get('match');
		if ($matchType != 'exact' && mb_strlen($query, 'utf-8') < $this->minQueryLength) {
			$this->view['message'] = sprintf('Трябва да въведете поне %d знака.', $this->minQueryLength);
			$this->responseStatusCode = 400;

			return $this->display('message');
		}

		return array(
			'text' => $query,
			'by'    => $request->get('by'),
			'match' => $matchType,
		);
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
