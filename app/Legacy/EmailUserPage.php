<?php namespace App\Legacy;

use App\Entity\User;

class EmailUserPage extends MailPage {

	protected
		$action = 'emailUser';

	public function __construct($fields) {
		parent::__construct($fields);

		$this->title = 'Пращане на писмо на потребител';
		$this->username = $this->request->value('username', '', 1);

		$this->mailSubject = $this->request->value('subject', 'Писмо чрез {SITENAME}');
		$this->mailMessage = $this->request->value('message', '');
		$this->mailSuccessMessage = 'Писмото беше изпратено.';
	}

	protected function processSubmission() {
		$err = $this->validateData();
		if ( !empty($err) ) {
			$this->addMessage($err, true);
			return '';
		}
		$err = $this->validateInput();
		if ( !empty($err) ) {
			$this->addMessage($err, true);
			return $this->makeForm();
		}

		$this->mailToName = $this->localUser->getRealname() == ''
			? $this->localUser->getUsername()
			: $this->localUser->getRealname();
		$this->mailToEmail = $this->localUser->getEmail();
		$this->mailFromName = $this->user->getUsername();
		$this->mailFromEmail = $this->user->getEmail();

		return parent::processSubmission();
	}

	protected function buildContent() {
		$err = $this->validateData();
		if ( ! empty($err) ) {
			$this->addMessage($err, true);
			return '';
		}

		return $this->makeForm();
	}

	protected function validateData() {
		if ( $this->user->isAnonymous() ) {
			return 'Необходимо е да се регистрирате и да посочите валидна електронна поща, за да можете да пращате писма на други потребители.';
		}
		if ($this->user->getEmail() == '') {
			$settingslink = $this->controller->generateUrl('user_edit', array('username' => $this->user->getUsername()));

			return "Необходимо е да посочите валидна електронна поща в <a href=\"$settingslink\">настройките си</a>, за да можете да пращате писма на други потребители.";
		}
		return '';
	}

	protected function validateInput() {
		if ( empty($this->username) ) {
			return 'Не е избран потребител.';
		}
		$this->localUser = $this->controller->em()->getUserRepository()->loadUserByUsername($this->username);
		if ( ! $this->localUser) {
			return "Не съществува потребител с име <strong>$this->username</strong>.";
		}
		if ($this->localUser->getAllowemail() == 0) {
			return "<strong>$this->username</strong> не желае да получава писма чрез {SITENAME}.";
		}
		if ( empty($this->mailSubject) ) {
			return 'Въведете тема на писмото!';
		}
		if ( empty($this->mailMessage) ) {
			return 'Въведете текст на писмото!';
		}
		return '';
	}

	protected function makeForm() {
		$ownsettingslink = $this->controller->generateUrl('user_edit', array('username' => $this->user->getUsername()));
		$fromuserlink = $this->makeUserLink($this->user->getUsername());
		$username = $this->out->textField('username', '', $this->username, 30, 30);
		$subject = $this->out->textField('subject', '', $this->mailSubject, 60, 200);
		$message = $this->out->textarea('message', '', $this->mailMessage, 20, 80);
		$submit = $this->out->submitButton('Изпращане на писмото');
		return <<<EOS

<p>Чрез долния формуляр можете да пратите писмо на потребител по електронната поща. Адресът, записан в <a href="$ownsettingslink">настройките ви</a>, ще се появи в полето „От“ на изпратеното писмо, така че получателят ще е в състояние да ви отговори.</p>
<form action="" method="post">
<fieldset>
	<legend>Писмо</legend>
	<table border="0"><tr>
		<td class="fieldname-left">От:</td>
		<td>$fromuserlink</td>
	</tr><tr>
		<td class="fieldname-left"><label for="username">До:</label></td>
		<td>$username</td>
	</tr><tr>
		<td class="fieldname-left"><label for="subject">Относно:</label></td>
		<td>$subject</td>
	</tr></table>
	<div><label for="message">Съобщение:</label><br />
	$message</div>
	<p>$submit</p>
</fieldset>
</form>
EOS;
	}

	protected function makeMailMessage() {
		return <<<EOS
$this->mailMessage

----
Това писмо е изпратено от $this->mailFromName <$this->mailFromEmail> чрез $this->sitename (http://chitanka.info).
EOS;
	}

}
