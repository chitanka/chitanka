<?php namespace App\Controller;

use App\Entity\Person;

class TranslatorController extends PersonController {
	protected function prepareViewForShow(Person $person, $format) {
		$this->prepareViewForShowTranslator($person, $format);
	}

	protected function getPersonRepository() {
		return $this->em()->getPersonRepository()->asTranslator();
	}
}
