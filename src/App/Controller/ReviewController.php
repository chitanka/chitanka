<?php namespace App\Controller;

use App\Service\ReviewService;

class ReviewController extends Controller {

	public function indexAction() {
		$reviews = ReviewService::getReviews();
		if (empty($reviews)) {
			return $this->displayText('No reviews found');
		}

		return $this->display('index', array(
			'reviews' => $reviews,
		));
	}
}
