<?php namespace App\Controller;

use App\Entity\Person;
use Symfony\Component\HttpFoundation\Request;

class TranslatorController extends PersonController {

	public function searchAction(Request $request, $_format) {
		if ($_format == 'suggest') {
			$items = $descs = $urls = [];
			$query = $request->query->get('q');
			$persons = $this->em()->getPersonRepository()->asTranslator()->getByQuery([
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => self::PAGE_COUNT_LIMIT,
			]);
			foreach ($persons as $person) {
				$items[] = $person['name'];
				$descs[] = '';
				$urls[] = $this->generateAbsoluteUrl('translator_show', ['slug' => $person['slug']]);
			}

			return [$query, $items, $descs, $urls];
		}
		return [];
	}

	protected function getShowTemplateParams(Person $person, $format) {
		return $this->getShowTemplateParamsTranslator($person, $format);
	}

	protected function getPersonRepository() {
		return $this->em()->getPersonRepository()->asTranslator();
	}
}
