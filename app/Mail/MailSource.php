<?php namespace App\Mail;

abstract class MailSource {

	const ANONYMOUS_EMAIL = 'anonymous@anonymous.net';
	const ANONYMOUS_NAME = 'Anonymous';

	public $name;

	public $email;

	public $subject;

	abstract public function getBody();

	public function getSender() {
		return array($this->getSenderEmail() => $this->getSenderName());
	}

	public function getSenderName() {
		return $this->name ?: self::ANONYMOUS_NAME;
	}

	public function getSenderEmail() {
		return $this->email ?: self::ANONYMOUS_EMAIL;
	}

	public function getSubject() {
		return $this->subject;
	}

}
