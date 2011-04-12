<?php
namespace Chitanka\LibBundle\Legacy;

use Chitanka\LibBundle\Util\String;

class MailPage extends Page {

	protected
		$action = 'mail',
		$mailSuccessMessage,
		$mailFailureMessage,
		$mailTo,
		$mailFrom,
		$mailSubject = '',
		$extraMailHeaders = array()
	;


	public function __construct($fields) {
		parent::__construct($fields);

		$this->logFile = $this->logDir . '/email.log';

// 		$this->name = $this->request->value('name', ADMIN);
// 		$this->email = $this->request->value('email', ADMIN_EMAIL);
		$this->name = $this->request->value('name');
		$this->email = $this->request->value('email');

		$this->mailToName = ADMIN;
		$this->mailToEmail = ADMIN_EMAIL;
		$this->mailFromName = SITENAME;
		$this->mailFromEmail = SITE_EMAIL;

		$this->mailSubject = 'Тема на писмото';
		$this->mailSuccessMessage = 'Съобщението беше изпратено.';
		$this->mailFailureMessage = 'Изглежда е станал някакъв фал при
			изпращането. Ако желаете, пробвайте още веднъж.';
		$this->mailMessage = '';
	}


	protected function processSubmission() {
		$mailer = $this->controller->get('mailer');

		//$mail->setReturnPath($this->mailFromEmail);

		$messageBody = $this->makeMailMessage();
		Legacy::fillOnEmpty($this->mailFromEmail, 'anonymous@anonymous.net');
		Legacy::fillOnEmpty($this->mailFromName, 'Анонимен');

		$from = array($this->mailFromEmail => $this->mailFromName);
		try {
			$message = \Swift_Message::newInstance($this->mailSubject)
				->setFrom($from)
				->setTo(array($this->mailToEmail => $this->mailToName))
				->setBody($messageBody);

			$headers = $message->getHeaders();
			//$headers->addPathHeader('Return-Path', $this->mailFromEmail);
			$headers->addMailboxHeader('Reply-To', $from);
			$headers->addTextHeader('X-Mailer', 'Mylib');
		}
		catch (Exception $e) {
			$this->addMessage('Станал е някакъв гаф. Може адреса да не е правилен.', true);
			file_put_contents($this->logFile, $e, FILE_APPEND);
			return $this->buildContent();
		}

		$this->logEmail($messageBody);
		try {
			$result = $mailer->send($message);
		}
		catch (\Exception $e) {
			$result = false;
			file_put_contents($this->logFile, $e, FILE_APPEND);
		}
		if ( $result == 0 ) {
			$this->addMessage($this->mailFailureMessage /*.
				'<br />Съобщението за грешка, между другото, гласи: <code>'.
				String::myhtmlentities( $res->getMessage() ) .'</code>'*/, true);
			return $this->buildContent();
		}
		$this->addMessage($this->mailSuccessMessage);
		return $this->makeSubmissionReturn();
	}


	protected function buildContent() {
		return $this->makeForm();
	}


	protected function setMailHeaders($mail) {
		$mail->setFrom($this->mailFromEmail, $this->mailFromName);
		$mail->setReturnPath($this->mailFromEmail);
		$mail->addHeader('X-Mailer', 'Mylib');
		foreach ( $this->extraMailHeaders as $name => $value ) {
			$mail->addHeader($name, $value);
		}
		return $mail;
	}

	protected function makeSubmissionReturn() { return ''; }

	protected function makeForm() { return ''; }

	protected function makeMailMessage() { return $this->mailMessage; }

	protected function logEmail($message, $headers = array()) {
		$sheaders = '';
		foreach ($headers as $header => $value) {
			$sheaders .= "$header: $value\n";
		}
		$date = date('Y-m-d H:i:s');
		$logString = <<<EOS
+++ EMAIL +++
[$date]
$sheaders
Subject: $this->mailSubject
To: $this->mailToName <$this->mailToEmail>
Message:
$message
--- EMAIL ---

EOS;
		file_put_contents($this->logFile, $logString, FILE_APPEND);
	}
}
