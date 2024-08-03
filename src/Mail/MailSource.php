<?php namespace App\Mail;

abstract class MailSource {

	const ANONYMOUS_EMAIL = 'anonymous@anonymous.net';
	const ANONYMOUS_NAME = 'Anonymous';

	public $name;

	public $email;

	public $subject;

	abstract public function getBody();

	public function getSender() {
		return [$this->getSenderEmail() => $this->getSenderName()];
	}

	/**
	 * @return string
	 */
	public function getSenderName() {
		return $this->name ?: self::ANONYMOUS_NAME;
	}

	/**
	 * @return string
	 */
	public function getSenderEmail() {
		return $this->email ?: self::ANONYMOUS_EMAIL;
	}

	/**
	 * @return string
	 */
	public function getSubject() {
		return $this->subject;
	}

}
