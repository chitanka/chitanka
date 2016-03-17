<?php namespace App\Service;

use App\Entity\User;

class Translation {

	private $messages;

	public function __construct() {
		$this->messages = $this->loadMessages();
	}

	public function getBookTypeChoices() {
		return $this->generateChoicesFor('book.type');
	}

	public function getCountryChoices() {
		return $this->generateChoicesFor('country');
	}

	public function getLabelGroupChoices() {
		return $this->generateChoicesFor('label.group');
	}

	public function getLanguageChoices() {
		return $this->generateChoicesFor('lang');
	}

	public function getPersonTypeChoices() {
		return $this->generateChoicesFor('person.type');
	}

	public function getRatingChoices() {
		return $this->generateChoicesFor('rating');
	}

	public function getTextTypeChoices() {
		return $this->generateChoicesFor('text.type.singular');
	}

	public function getUserGroupChoices() {
		$groups = User::getGroupList();
		return $this->generateChoicesFromCodes($groups, 'user.group');
	}

	private function generateChoicesFor($key) {
		$codes = array_keys($this->messages[$key]);
		return $this->generateChoicesFromCodes($codes, $key);
	}

	private function generateChoicesFromCodes($codes, $prefix) {
		return array_combine(array_map(function($lang) use($prefix) { return "$prefix.$lang"; }, $codes), $codes);
	}

	private function loadMessages() {
		return require __DIR__.'/../Resources/translations/messages.bg.php';
	}
}
