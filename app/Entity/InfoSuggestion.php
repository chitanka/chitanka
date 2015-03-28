<?php namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as AppAssert;
use App\Mail\MailSource;

class InfoSuggestion extends MailSource {

	private static $subjects = [
		'orig_title' => 'Информация за оригинално заглавие',
		'orig_year' => 'Информация за година на написване или първа публикация',
		'translator' => 'Информация за преводач',
		'trans_year' => 'Информация за година на превод',
		// TODO enable annotation suggestions for books
		//'annotation' => 'Предложение за анотация',
	];

	/**
	 */
	public $name;

	/**
	 * @Assert\Email()
	 */
	public $email;

	/**
	 * @Assert\NotBlank()
	 * @AppAssert\NotSpam()
	 */
	public $info;

	private $type;
	private $text;

	public function __construct($type, Text $text) {
		$this->setType($type);
		$this->text = $text;
	}

	public function getBody() {
		return <<<EOS
Произведение: „{$this->text->getTitle()}“

http://chitanka.info/admin/text/{$this->text->getId()}/edit

$this->info
EOS;
	}

	public function getSubject() {
		return self::$subjects[$this->type];
	}

	public function setSender(User $sender) {
		$this->email = $sender->getEmail();
		$this->name = $sender->getUsername();
	}

	private function setType($type) {
		if (!isset(self::$subjects[$type])) {
			throw new \InvalidArgumentException("Type '$type' is not supported");
		}
		$this->type = $type;
	}
}
