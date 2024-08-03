<?php namespace App\Service;

class WikiReaderPage {

	public $name;
	public $intro;
	public $content;

	public function __construct($name) {
		$this->name = $name;
	}
}
