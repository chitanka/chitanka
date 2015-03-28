<?php namespace App\Service;

use App\Entity\EntityManager;
use App\Util\String;
use Symfony\Component\HttpFoundation\Request;

class SearchService {

	private static $minQueryLength = 3;
	private static $maxQueryLength = 60;

	private $em;

	public function __construct(EntityManager $em) {
		$this->em = $em;
	}

	public function prepareQuery(Request $request, $format = 'html') {
		$params = $request->query;
		$query = trim($params->get('q'));

		if (empty($query)) {
			return [
				'_template' => "App:Search:list_top_strings.$format.twig",
				'latest_strings' => $this->em->getSearchStringRepository()->getLatest(30),
				'top_strings' => $this->em->getSearchStringRepository()->getTop(30),
			];
		}

		$query = String::fixEncoding($query);

		$matchType = $params->get('match');
		if ($matchType != 'exact') {
			try {
				$this->validateQueryLength($query);
			} catch (\InvalidArgumentException $e) {
				return [
					'_template' => "App:Search:message.$format.twig",
					'_status' => 400,
					'message' => $e->getMessage(),
				];
			}
		}

		return [
			'text'  => $query,
			'by'    => $request->get('by'),
			'match' => $matchType,
		];
	}

	/**
	 * @param string $query
	 */
	private function validateQueryLength($query) {
		$queryLength = mb_strlen($query, 'utf-8');
		if ($queryLength < self::$minQueryLength) {
			throw new \InvalidArgumentException(sprintf('Трябва да въведете поне %d знака.', self::$minQueryLength));
		}
		if ($queryLength > self::$maxQueryLength) {
			throw new \InvalidArgumentException(sprintf('Не може да въвеждате повече от %d знака.', self::$maxQueryLength));
		}
	}

}
