<?php namespace App\Mail;

interface MailSource {

	const ANONYMOUS_EMAIL = 'anonymous@anonymous.net';
	const ANONYMOUS_NAME = 'Anonymous';

	public function getBody();

	public function getSender();

	public function getSubject();

}
