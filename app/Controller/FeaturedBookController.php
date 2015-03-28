<?php namespace App\Controller;

class FeaturedBookController extends Controller {

	public function indexAction() {
		return [
			'books' => $this->em()->getFeaturedBookRepository()->getLatest(100),
		];
	}

	public function bookAction() {
		return $this->render('App:FeaturedBook:book.html.twig', [
			'book' => $this->em()->getFeaturedBookRepository()->getRandom()
		]);
	}
}
