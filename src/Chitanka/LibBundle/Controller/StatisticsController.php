<?php
namespace Chitanka\LibBundle\Controller;

class StatisticsController extends Controller
{
	public function indexAction()
	{
		$this->view = array(
			'count' => array(
				'authors'       => $this->getPersonRepository()->asAuthor()->getCount(),
				'translators'   => $this->getPersonRepository()->asTranslator()->getCount(),
				'texts'         => $this->getTextRepository()->getCount(),
				'series'        => $this->getSeriesRepository()->getCount(),
				'labels'        => $this->getLabelRepository()->getCount(),
				'books'         => $this->getBookRepository()->getCount(),
				'sequences'     => $this->getSequenceRepository()->getCount(),
				'categories'    => $this->getCategoryRepository()->getCount(),
				'text_comments' => $this->getTextCommentRepository()->getCount('e.is_shown = 1'),
				'users'         => $this->getUserRepository()->getCount(),
			),
			'author_countries'  => $this->getAuthorCountries(),
			'text_types'        => $this->getTextTypes(),
		);

		return $this->display('index');
	}

	private function getAuthorCountries()
	{
		$authors = $this->getPersonRepository()->asAuthor()->getCountsByCountry();
		arsort($authors);
		return $authors;
	}

	private function getTextTypes()
	{
		$texts = $this->getTextRepository()->getCountsByType();
		arsort($texts);
		return $texts;
	}
}
