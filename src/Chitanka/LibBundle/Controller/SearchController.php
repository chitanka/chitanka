<?php

namespace Chitanka\LibBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Chitanka\LibBundle\Pagination\Pager;
use Chitanka\LibBundle\Entity\SearchString;
use Chitanka\LibBundle\Util\String;

class SearchController extends Controller
{
	protected $responseAge = 86400; // 24 hours
	private $minQueryLength = 3;
	private $maxQueryLength = 60;

	public function indexAction($_format)
	{
		if ($_format == 'osd') {
			return $this->display("index.$_format");
		}
		if (($query = $this->getQuery($_format)) instanceof Response) {
			return $query;
		}

		$lists = array(
			'persons'      => $this->getPersonRepository()->getByNames($query['text'], 15),
			'texts'        => $this->getTextRepository()->getByTitles($query['text'], 15),
			'books'        => $this->getBookRepository()->getByTitles($query['text'], 15),
			'series'       => $this->getSeriesRepository()->getByNames($query['text'], 15),
			'sequences'    => $this->getSequenceRepository()->getByNames($query['text'], 15),
			//'work_entries' => $this->getWorkEntryRepository()->getByTitleOrAuthor($query['text']),
			'labels'       => $this->getLabelRepository()->getByNames($query['text']),
			'categories'   => $this->getCategoryRepository()->getByNames($query['text']),
		);

		$found = array_sum(array_map('count', $lists)) > 0;

		if ($found) {
			$this->logSearch($query['text']);
		} else {
			$this->responseStatusCode = 404;
		}

		$this->view = array(
			'query' => $query,
			'found' => $found,
		) + $lists;

		return $this->display("index.$_format");
	}


	public function personsAction(Request $request, $_format)
	{
		if ($_format == 'osd') {
			return $this->display("search.$_format", 'Person');
		}
		if ($_format == 'suggest') {
			$items = $descs = $urls = array();
			$query = $request->query->get('q');
			$persons = $this->getPersonRepository()->getByQuery(array(
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => 10,
			));
			foreach ($persons as $person) {
				$items[] = $person['name'];
				$descs[] = '';
				$urls[] = $this->generateUrl('person_show', array('slug' => $person['slug']), true);
			}

			return $this->displayJson(array($query, $items, $descs, $urls));
		}
		if (($query = $this->getQuery($_format)) instanceof Response) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'name,orig_name';
		}
		$persons = $this->getPersonRepository()->getByQuery($query);
		if ( ! ($found = count($persons) > 0)) {
			$this->responseStatusCode = 404;
		}
		$this->view = array(
			'query'   => $query,
			'persons' => $persons,
			'found'   => $found,
		);

		return $this->display("search.$_format", 'Person');
	}

	public function authorsAction(Request $request, $_format)
	{
		if ($_format == 'suggest') {
			$items = $descs = $urls = array();
			$query = $request->query->get('q');
			$persons = $this->getPersonRepository()->asAuthor()->getByQuery(array(
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => 10,
			));
			foreach ($persons as $person) {
				$items[] = $person['name'];
				$descs[] = '';
				$urls[] = $this->generateUrl('author_show', array('slug' => $person['slug']), true);
			}

			return $this->displayJson(array($query, $items, $descs, $urls));
		}
		return $this->display("search.$_format", 'Author');
	}

	public function translatorsAction(Request $request, $_format)
	{
		if ($_format == 'suggest') {
			$items = $descs = $urls = array();
			$query = $request->query->get('q');
			$persons = $this->getPersonRepository()->asTranslator()->getByQuery(array(
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => 10,
			));
			foreach ($persons as $person) {
				$items[] = $person['name'];
				$descs[] = '';
				$urls[] = $this->generateUrl('translator_show', array('slug' => $person['slug']), true);
			}

			return $this->displayJson(array($query, $items, $descs, $urls));
		}
		return $this->display("search.$_format", 'Translator');
	}

	public function textsAction(Request $request, $_format)
	{
		if ($_format == 'osd') {
			return $this->display("search.$_format", 'Text');
		}
		if ($_format == 'suggest') {
			$items = $descs = $urls = array();
			$query = $request->query->get('q');
			$texts = $this->getTextRepository()->getByQuery(array(
				'text'  => $query,
				'by'    => 'title',
				'match' => 'prefix',
				'limit' => 10,
			));
			foreach ($texts as $text) {
				$items[] = $text['title'];
				$descs[] = '';
				$urls[] = $this->generateUrl('text_show', array('id' => $text['id']), true);
			}

			return $this->displayJson(array($query, $items, $descs, $urls));
		}
		if (($query = $this->getQuery($_format)) instanceof Response) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'title,subtitle,orig_title';
		}
		$texts = $this->getTextRepository()->getByQuery($query);
		if ( ! ($found = count($texts) > 0)) {
			$this->responseStatusCode = 404;
		}
		$this->view = array(
			'query' => $query,
			'texts' => $texts,
			'found' => $found,
		);

		return $this->display("search.$_format", 'Text');
	}


	public function booksAction(Request $request, $_format)
	{
		if ($_format == 'osd') {
			return $this->display("search.$_format", 'Book');
		}
		if ($_format == 'suggest') {
			$items = $descs = $urls = array();
			$query = $request->query->get('q');
			$books = $this->getBookRepository()->getByQuery(array(
				'text'  => $query,
				'by'    => 'title',
				'match' => 'prefix',
				'limit' => 10,
			));
			foreach ($books as $book) {
				$items[] = $book['title'];
				$descs[] = '';
				$urls[] = $this->generateUrl('book_show', array('id' => $book['id']), true);
			}

			return $this->displayJson(array($query, $items, $descs, $urls));
		}
		if (($query = $this->getQuery($_format)) instanceof Response) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'title,subtitle,orig_title';
		}
		$books = $this->getBookRepository()->getByQuery($query);
		if ( ! ($found = count($books) > 0)) {
			$this->responseStatusCode = 404;
		}
		$this->view = array(
			'query' => $query,
			'books' => $books,
			'found' => $found,
		);

		return $this->display("search.$_format", 'Book');
	}


	public function seriesAction(Request $request, $_format)
	{
		if ($_format == 'osd') {
			return $this->display("search.$_format", 'Series');
		}
		if ($_format == 'suggest') {
			$items = $descs = $urls = array();
			$query = $request->query->get('q');
			$series = $this->getSeriesRepository()->getByQuery(array(
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => 10,
			));
			foreach ($series as $serie) {
				$items[] = $serie['name'];
				$descs[] = '';
				$urls[] = $this->generateUrl('series_show', array('slug' => $serie['slug']), true);
			}

			return $this->displayJson(array($query, $items, $descs, $urls));
		}
		if (($query = $this->getQuery($_format)) instanceof Response) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'name,orig_name';
		}
		$series = $this->getSeriesRepository()->getByQuery($query);
		if ( ! ($found = count($series) > 0)) {
			$this->responseStatusCode = 404;
		}
		$this->view = array(
			'query'  => $query,
			'series' => $series,
			'found'  => $found,
		);

		return $this->display("search.$_format", 'Series');
	}


	public function sequencesAction(Request $request, $_format)
	{
		if ($_format == 'osd') {
			return $this->display("search.$_format", 'Sequence');
		}
		if ($_format == 'suggest') {
			$items = $descs = $urls = array();
			$query = $request->query->get('q');
			$sequences = $this->getSequenceRepository()->getByQuery(array(
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => 10,
			));
			foreach ($sequences as $sequence) {
				$items[] = $sequence['name'];
				$descs[] = '';
				$urls[] = $this->generateUrl('sequence_show', array('slug' => $sequence['slug']), true);
			}

			return $this->displayJson(array($query, $items, $descs, $urls));
		}
		if (($query = $this->getQuery($_format)) instanceof Response) {
			return $query;
		}

		if (empty($query['by'])) {
			$query['by'] = 'name';
		}
		$sequences = $this->getSequenceRepository()->getByQuery($query);
		if ( ! ($found = count($sequences) > 0)) {
			$this->responseStatusCode = 404;
		}
		$this->view = array(
			'query'     => $query,
			'sequences' => $sequences,
			'found'     => $found,
		);

		return $this->display("search.$_format", 'Sequence');
	}


	private function getQuery($_format = 'html')
	{
		$request = $this->get('request')->query;
		$query = trim($request->get('q'));

		if ( ! $query) {
			$this->view = array(
				'latest_strings' => $this->getSearchStringRepository()->getLatest(30),
				'top_strings' => $this->getSearchStringRepository()->getTop(30),
			);

			return $this->display("list_top_strings.$_format");
		}

		$query = String::fixEncoding($query);

		$matchType = $request->get('match');
		if ($matchType != 'exact') {
			$queryLength = mb_strlen($query, 'utf-8');
			$error = '';
			if ($queryLength < $this->minQueryLength) {
				$error = sprintf('Трябва да въведете поне %d знака.', $this->minQueryLength);
			} else if ($queryLength > $this->maxQueryLength) {
				$error = sprintf('Не може да въвеждате повече от %d знака.', $this->maxQueryLength);
			}
			if ($error) {
				$this->view['message'] = $error;
				$this->responseStatusCode = 400;

				return $this->display("message.$_format");
			}
		}

		return array(
			'text'  => $query,
			'by'    => $request->get('by'),
			'match' => $matchType,
		);
	}

	private function logSearch($query)
	{
		$searchString = $this->getSearchStringRepository()->findOneBy(array('name' => $query));
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
			'strings' => $this->getSearchStringRepository()->getLatest($limit),
		);

		return $this->display('top_strings');
	}
}
