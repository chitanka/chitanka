<?php

namespace Chitanka\LibBundle\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Chitanka\LibBundle\Pagination\Pager;
use Chitanka\LibBundle\Legacy\Legacy;

class PersonController extends Controller
{
	public function indexAuthorsAction()
	{
		return $this->display('index_authors');
	}

	public function indexTranslatorsAction()
	{
		return $this->display('index_translators');
	}


	public function listAuthorsAction($by, $letter, $page, $_format)
	{
		$request = $this->get('request')->query;
		$country = $request->get('country', '');
		$limit = 100;

		$repo = $this->getRepository('Person')->asAuthor();
		$filters = array(
			'by'      => $by,
			'prefix'  => $letter,
			'country' => $country,
		);
		$this->view = compact('by', 'letter', 'country') + array(
			'authors' => $repo->getBy($filters, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countBy($filters)
			)),
			'route' => 'authors_by_'.$by.'_name',
			'route_params' => array('letter' => $letter, 'by' => $by),
		);

		return $this->display('list_authors');
	}

	public function listAuthorsByCountryAction($country, $page, $_format)
	{
		$request = $this->get('request')->query;
		$by      = $request->get('by', 'first');
		$limit = 100;

		$repo = $this->getRepository('Person')->asAuthor();
		$filters = array(
			'by'      => $by,
			'country' => $country,
		);
		$this->view = compact('by', 'country') + array(
			'authors' => $repo->getBy($filters, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countBy($filters)
			)),
			'route' => 'authors_by_country',
			'route_params' => array('country' => $country/*, 'by' => $by*/, '_format' => $_format),
		);
		$this->responseFormat = $_format;

		return $this->display('list_authors_by_country');
	}


	public function listTranslatorsAction($by, $letter, $page, $_format)
	{
		$request = $this->get('request')->query;
		$country = $request->get('country', '');
		$limit = 100;

		$repo = $this->getRepository('Person')->asTranslator();
		$filters = array(
			'by'      => $by,
			'prefix'  => $letter,
			'country' => $country,
		);
		$this->view = compact('by', 'letter', 'country') + array(
			'translators' => $repo->getBy($filters, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countBy($filters)
			)),
			'route' => 'translators_by_'.$by.'_name',
			'route_params' => array('letter' => $letter, 'by' => $by),
		);

		return $this->display('list_translators');
	}



	public function showAction($slug, $_format)
	{
		$this->responseAge = 86400; // 24 hours

		$person = $this->getRepository('Person')->findOneBy(array('slug' => $slug));

		if ( ! $person) {
			$person = $this->getRepository('Person')->findOneBy(array('name' => $slug));
			if ($person) {
				return $this->urlRedirect($this->generateUrl('person_show', array('slug' => $person->getSlug())), true);
			}
			throw new NotFoundHttpException("Няма личност с код $slug.");
		}

		$textsAsAuthor = $this->getRepository('Text')->findByAuthor($person);
		$textsAsTranslator = $this->getRepository('Text')->findByTranslator($person);
		$this->view = array(
			'person' => $person,
			'texts_as_author' => $textsAsAuthor,
			'texts_as_translator' => $textsAsTranslator,
		);

		if ( count($books = $this->getRepository('Book')->getByAuthor($person)) ) {
			$this->view['books'] = $books;
		}
		if ($person->getInfo() != '') {
			list($prefix, $name) = explode(':', $person->getInfo());
			$site = $this->getRepository('WikiSite')->findOneBy(array('code' => $prefix));
			$this->view['info'] = Legacy::getMwContent($site->getUrl($name));
			$this->view['info_intro'] = $site->getIntro();
		}
		$this->responseFormat = $_format;

		return $this->display('show');
	}

	public function showRedirectAction($name)
	{
		$person = $this->getRepository('Person')->findOneBy(array('name' => $name));
		if ($person) {
			return $this->urlRedirect($this->generateUrl('person_show', array('slug' => $person->getSlug())), true);
		}
		throw new NotFoundHttpException("Няма личност с име $slug.");
	}


	public function suggest($slug)
	{
		return $this->lecacyPage('Info');
	}

}
