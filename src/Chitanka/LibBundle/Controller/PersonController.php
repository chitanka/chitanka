<?php
namespace Chitanka\LibBundle\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Chitanka\LibBundle\Pagination\Pager;
use Chitanka\LibBundle\Legacy\Legacy;
use Chitanka\LibBundle\Util\String;

class PersonController extends Controller
{
	public function indexAction($_format)
	{
		$this->responseFormat = $_format;

		return $this->display('index');
	}

	public function listByAlphaIndexAction($by, $_format)
	{
		$this->view = array(
			'by' => $by,
		);
		$this->responseFormat = $_format;

		return $this->display('list_by_alpha_index');
	}

	public function listByAlphaAction($by, $letter, $page, $_format)
	{
		$request = $this->get('request')->query;
		$country = $request->get('country', '');
		$limit = 100;

		$repo = $this->getPersonRepository();
		$filters = array(
			'by'      => $by,
			'prefix'  => $letter,
			'country' => $country,
		);
		$this->view = array(
			'by'      => $by,
			'letter'  => $letter,
			'country' => $country,
			'persons' => $repo->getBy($filters, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countBy($filters)
			)),
			'route' => $this->getCurrentRoute(),
			'route_params' => array('letter' => $letter, 'by' => $by),
		);
		$this->responseFormat = $_format;

		return $this->display('list_by_alpha');
	}

	public function showAction($slug, $_format)
	{
		$this->responseAge = 86400; // 24 hours

		$person = $this->getRepository('Person')->findBySlug(String::slugify($slug));

		if ($person == null) {
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
			// TODO move this in the entity
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
