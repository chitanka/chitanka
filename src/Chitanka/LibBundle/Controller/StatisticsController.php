<?php

namespace Chitanka\LibBundle\Controller;

class StatisticsController extends Controller
{
	public function indexAction()
	{
		$authors = $this->getRepository('Person')->asAuthor()->getCountsByCountry();
		arsort($authors);
		$texts = $this->getRepository('Text')->getCountsByType();
		arsort($texts);

		$this->view = array(
			'count' => array(
				'authors' => $this->getRepository('Person')->asAuthor()->getCount(),
				'translators' => $this->getRepository('Person')->asTranslator()->getCount(),
				'texts' => $this->getRepository('Text')->getCount(),
				'series' => $this->getRepository('Series')->getCount(),
				'labels' => $this->getRepository('Label')->getCount(),
				'books' => $this->getRepository('Book')->getCount(),
				'sequences' => $this->getRepository('Sequence')->getCount(),
				'categories' => $this->getRepository('Category')->getCount(),
				'text_comments' => $this->getRepository('TextComment')->getCount('e.is_shown = 1'),
				'users' => $this->getRepository('User')->getCount(),
			),
			'author_countries' => $authors,
			'text_types' => $texts,
		);

		return $this->display('index');
	}

}
