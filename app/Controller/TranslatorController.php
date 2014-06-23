<?php namespace App\Controller;

use App\Entity\Person;
use Symfony\Component\HttpFoundation\Request;

class TranslatorController extends PersonController {

	public function searchAction(Request $request, $_format) {
		if ($_format == 'suggest') {
			$items = $descs = $urls = array();
			$query = $request->query->get('q');
			$persons = $this->em()->getPersonRepository()->asTranslator()->getByQuery(array(
				'text'  => $query,
				'by'    => 'name',
				'match' => 'prefix',
				'limit' => 10,
			));
			foreach ($persons as $person) {
				$items[] = $person['name'];
				$descs[] = '';
				$urls[] = $this->generateUrl('translator_show', array('slug' => $person['slug']), true);
			}

			return $this->asJson(array($query, $items, $descs, $urls));
		}
		return array();
	}

	protected function getShowTemplateParams(Person $person, $format) {
		return $this->getShowTemplateParamsTranslator($person, $format);
	}

	protected function getPersonRepository() {
		return $this->em()->getPersonRepository()->asTranslator();
	}
}
