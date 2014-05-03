<?php namespace App\Mail;

abstract class MailSource {

	const ANONYMOUS_EMAIL = 'anonymous@anonymous.net';
	const ANONYMOUS_NAME = 'Anonymous';

	public $name;

	public $email;

	public $subject;

	abstract public function getBody();

	public function getSender() {
		return array($this->getEmail() => $this->getName());
	}

	protected function getName() {
		return $this->name ?: self::ANONYMOUS_NAME;
	}

	protected function getEmail() {
		return $this->email ?: self::ANONYMOUS_EMAIL;
	}

	public function getSubject() {
		return $this->subject;
	}

}
