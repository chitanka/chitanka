<?php
namespace Chitanka\LibBundle\Controller;

use Chitanka\LibBundle\Entity\Person;

class TranslatorController extends PersonController
{
	protected function prepareViewForShow(Person $person, $format)
	{
		$this->prepareViewForShowTranslator($person, $format);
	}

	protected function getPersonRepository()
	{
		return parent::getPersonRepository()->asTranslator();
	}
}
