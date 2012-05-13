<?php
namespace Chitanka\LibBundle\Controller;

use Chitanka\LibBundle\Pagination\Pager;
use Chitanka\LibBundle\Entity\Person;

class AuthorController extends PersonController
{
	public function listByCountryIndexAction($by, $_format)
	{
		$this->view = array(
			'by' => $by,
			'countries' => $this->getPersonRepository()->getCountryList()
		);
		$this->responseFormat = $_format;

		return $this->display('list_by_country_index');
	}

	public function listByCountryAction($country, $by, $page, $_format)
	{
		$limit = 100;

		$repo = $this->getPersonRepository();
		$filters = array(
			'by'      => $by,
			'country' => $country,
		);
		$this->view = array(
			'by'      => $by,
			'country' => $country,
			'persons' => $repo->getBy($filters, $page, $limit),
			'pager'    => new Pager(array(
				'page'  => $page,
				'limit' => $limit,
				'total' => $repo->countBy($filters)
			)),
			'route' => $this->getCurrentRoute(),
			'route_params' => array('country' => $country, 'by' => $by, '_format' => $_format),
		);
		$this->responseFormat = $_format;

		return $this->display('list_by_country');
	}

	protected function prepareViewForShow(Person $person, $format)
	{
		$this->prepareViewForShowAuthor($person, $format);
	}

	protected function getPersonRepository()
	{
		return parent::getPersonRepository()->asAuthor();
	}
}
