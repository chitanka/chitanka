<?php namespace App\Service;

use App\Legacy\Legacy;

class ReviewService {

	/**
	 * @param int $limit
	 * @param bool $random
	 */
	public static function getReviews($limit = null, $random = false) {
		$reviews = [];
		$feedUrl = 'http://blog.chitanka.info/section/reviews/feed';
		$feed = Legacy::getFromUrlOrCache($feedUrl, $days = 0.02);
		if (empty($feed) || strpos($feed, '<atom') === false) {
			return $reviews;
		}

		$feedTree = new \SimpleXMLElement($feed);
		foreach ($feedTree->xpath('//item') as $item) {
			$content = $item->children('content', true)->encoded;
			if (preg_match('|<img src="(.+)" title="„(.+)“ от (.+)"|U', $content, $matches)) {
				$reviews[] = [
					'id' => 0,
 					'author' => $matches[3],
					'title'  => $matches[2],
					'url'    => $item->link->__toString(),
					'cover'  => $matches[1],
					'description' => $item->description,
				];
			}
		}

		if ($random) {
			shuffle($reviews);
		}

		if ($limit) {
			$reviews = array_slice($reviews, 0, $limit);
		}

		return $reviews;
	}

	/**
	 * @param bool $random
	 */
	public static function getReview($random = false) {
		$reviews = self::getReviews(1, $random);
		return $reviews ? $reviews[0] : null;
	}
}
