<?php
namespace Chitanka\LibBundle\Controller;

class TranslatorController extends PersonController
{
	protected function getPersonRepository()
	{
		return parent::getPersonRepository()->asTranslator();
	}
}
