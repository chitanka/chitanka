<?php namespace App\Controller;

class StatisticsController extends Controller {

	public function indexAction() {
		return [
			'count' => [
				'authors'       => $this->em()->getPersonRepository()->asAuthor()->getCount(),
				'translators'   => $this->em()->getPersonRepository()->asTranslator()->getCount(),
				'texts'         => $this->em()->getTextRepository()->getCount(),
				'series'        => $this->em()->getSeriesRepository()->getCount(),
				'labels'        => $this->em()->getLabelRepository()->getCount(),
				'books'         => $this->em()->getBookRepository()->getCount(),
				'books_wo_cover'=> $this->em()->getBookRepository()->getCountWithMissingCover(),
				'sequences'     => $this->em()->getSequenceRepository()->getCount(),
				'categories'    => $this->em()->getCategoryRepository()->getCount(),
				'text_comments' => $this->em()->getTextCommentRepository()->getCount('e.is_shown = 1'),
				'users'         => $this->em()->getUserRepository()->getCount(),
			],
			'author_countries'  => $this->getAuthorCountries(),
			'text_types'        => $this->getTextTypes(),
		];
	}

	private function getAuthorCountries() {
		$authors = $this->em()->getPersonRepository()->asAuthor()->getCountsByCountry();
		arsort($authors);
		return $authors;
	}

	private function getTextTypes() {
		$texts = $this->em()->getTextRepository()->getCountsByType();
		arsort($texts);
		return $texts;
	}
}
