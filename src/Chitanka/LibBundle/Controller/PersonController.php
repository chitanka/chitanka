<?php

namespace Chitanka\LibBundle\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Chitanka\LibBundle\Pagination\Pager;
use Chitanka\LibBundle\Legacy\Legacy;

class PersonController extends Controller
{
	protected $repository = 'Person';

	public function indexAuthorsAction()
	{
		return $this->display('index_authors');
	}

	public function indexTranslatorsAction()
	{
		return $this->display('index_translators');
	}

	public function listAuthorsAction($letter, $page)
	{
		$request = $this->get('request')->query;
		$by      = $request->get('by', 'first');
		$country = $request->get('country', '');
		$limit = 100;

		$repo = $this->getRepository()->asAuthor();
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
			'route' => 'authors_by_letter',
			'route_params' => array('letter' => $letter, 'by' => $by),
		);

		return $this->display('list_authors');
	}

	public function listAuthorsByCountryAction($country, $page)
	{
		$request = $this->get('request')->query;
		$by      = $request->get('by', 'first');
		$limit = 100;

		$repo = $this->getRepository()->asAuthor();
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
			'route_params' => array('country' => $country/*, 'by' => $by*/),
		);

		return $this->display('list_authors_by_country');
	}


	public function listTranslatorsAction($letter, $page)
	{
		$request = $this->get('request')->query;
		$by      = $request->get('by', 'first');
		$country = $request->get('country', '');
		$limit = 100;

		$repo = $this->getRepository()->asTranslator();
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
			'route' => 'translators_by_letter',
			'route_params' => array('letter' => $letter, 'by' => $by),
		);

		return $this->display('list_translators');
	}



	public function showAction($slug)
	{
		$person = $this->getRepository()->findOneBy(array('slug' => $slug));

		if ( ! $person) {
			$person = $this->getRepository()->findOneBy(array('name' => $slug));
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
			'is_author' => ! empty($textsAsAuthor),
			'is_translator' => ! empty($textsAsTranslator),
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

		return $this->display('show');
	}

	public function showRedirectAction($name)
	{
		$person = $this->getRepository()->findOneBy(array('name' => $name));
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
