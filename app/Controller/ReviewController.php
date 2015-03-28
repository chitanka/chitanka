<?php namespace App\Controller;

use App\Service\ReviewService;

class ReviewController extends Controller {

	public function indexAction() {
		$reviews = ReviewService::getReviews();
		if (empty($reviews)) {
			return $this->asText('No reviews found');
		}
		return [
			'reviews' => $reviews,
		];
	}
}
