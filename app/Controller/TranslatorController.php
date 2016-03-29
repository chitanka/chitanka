<?php namespace App\Controller;

use App\Entity\Person;
use Symfony\Component\HttpFoundation\Request;

class TranslatorController extends PersonController {

	public function searchAction(Request $request, $_format) {
		if ($_format == 'suggest') {
			$query = $request->query->get('q');
			$persons = $this->findByQuery([
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => self::PAGE_COUNT_LIMIT,
			]);
			$items = $descs = $urls = [];
			foreach ($persons as $person) {
				$items[] = $person->getName();
				$descs[] = '';
				$urls[] = $this->generateAbsoluteUrl('translator_show', ['slug' => $person->getSlug()]);
			}
			return [$query, $items, $descs, $urls];
		}
		return [];
	}

	protected function getShowTemplateParams(Person $person, $format) {
		return $this->getShowTemplateParamsTranslator($person, $format);
	}

	protected function getPersonRepository() {
		return parent::getPersonRepository()->asTranslator();
	}
}
