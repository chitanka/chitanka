<?php
namespace Chitanka\LibBundle\Legacy;

use Chitanka\LibBundle\Entity\User;

class RegisterPage extends Page {

	protected
		$action = 'register',
		$nonEmptyFields = array('username', 'password', 'passwordRe'),
		$mainFields = array('username', 'password', 'passwordRe', 'realname', 'email');

	private
		$invalidReferers = array('login', 'logout', 'register', 'sendNewPassword');


	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = 'Регистрация';
		$this->attempt = (int) $this->request->value('attempt', 1);
		$this->mainFields = $this->nonEmptyFields + $this->mainFields;
		foreach ($this->mainFields as $field) {
			$this->$field = trim($this->request->value($field, ''));
		}
		$this->news = $this->request->checkbox('news');
		$this->returnto = $this->request->value('returnto', $this->request->referer());
		foreach ($this->invalidReferers as $invalidReferer) {
			if ( strpos($this->returnto, $invalidReferer) !== false ) {
				$this->returnto = '';
			}
		}

		$this->initCaptchaFields();
	}


	protected function processSubmission() {
		$err = $this->validateInput();
		$this->attempt++;
		if ( !empty($err) ) {
			$this->addMessage($err, true);
			return $this->buildContent();
		}
		if ($this->userExists() || $this->emailExists()) {
			return $this->buildContent();
		}

		$user = new User;
		$user->setUsername($this->username);
		$user->setRealname($this->realname);
		$user->setPassword($this->password);
		$user->setEmail($this->email);
		$user->setAllowemail(1);
		$user->setNews((int) $this->news);

		$em = $this->controller->getEntityManager();
		$em->persist($user);
		$em->flush();

		$this->addMessage("Регистрирахте се в <em>$this->sitename</em> като $this->username.");

		return '';
	}


	protected function validateInput()
	{
		if ( ! $this->verifyCaptchaAnswer() ) {
			return 'Не сте отговорили правилно на въпроса уловка.';
		}

		foreach ($this->nonEmptyFields as $nonEmptyField) {
			if ( empty($this->$nonEmptyField) ) {
				return 'Не сте попълнили всички полета.';
			}
		}
		if ( !$this->isValidPassword() ) {
			return 'Двете въведени пароли се различават.';
		}
		$isValid = User::isValidUsername($this->username);
		if ( $isValid !== true ) {
			return "Знакът „{$isValid}“ не е позволен в потребителското име.";
		}
		$res = Legacy::validateEmailAddress($this->email);
		if ($res == 0) {
			return 'Въведеният адрес за електронна поща е невалиден.';
		}
		if ($res == -1 && $this->attempt == 1) {
			return 'Въведеният адрес за електронна поща е валиден, но е леко странен. Проверете дали не сте допуснали грешка.';
		}
		return '';
	}


	protected function isValidPassword() {
		return strcmp($this->password, $this->passwordRe) === 0;
	}


	protected function userExists() {
		$key = array('username' => $this->username);
		if ( $this->db->exists(DBT_USER, $key) ) {
			$this->addMessage("Името <strong>$this->username</strong> вече е заето.", true);
			return true;
		}
		return false;
	}


	protected function emailExists($notUsername = null) {
		if ( empty($this->email) ) {
			return false;
		}

		$emailKey = array('email' => $this->email);
		if ( !is_null($notUsername) ) {
			$emailKey['username'] = array('!=', $notUsername);
		}
		if ( $this->db->exists(DBT_USER, $emailKey) ) {
			$this->addMessage("Пощенският адрес <strong>{$this->email}</strong> вече се ползва от друг потребител.", true);
			$sendname = $this->controller->generateUrl('request_username');
			$this->addMessage("Ако сте забравили потребителското си име, можете <a href=\"$sendname\">да поискате напомняне за него</a>.");

			return true;
		}

		return false;
	}


	protected function buildContent() {
		$login = $this->controller->generateUrl('login');
		$attempt = $this->out->hiddenField('attempt', $this->attempt);
		$returnto = $this->out->hiddenField('returnto', $this->returnto);
		$username = $this->out->textField('username', '', $this->username, 25, 50, 2);
		$password = $this->out->passField('password', '', $this->password, 25, 40, 3);
		$passwordRe = $this->out->passField('passwordRe', '', $this->passwordRe, 25, 40, 4);
		$realname = $this->out->textField('realname', '', $this->realname, 25, 50, 5);
		$email = $this->out->textField('email', '', $this->email, 25, 60, 6);
		$news = $this->out->checkbox('news', '', false, 'Получаване на месечен бюлетин', null, 7);
		$historyLink = $this->controller->generateUrl('new');
		$submit = $this->out->submitButton('Регистриране', '', 50);

		$question = $this->makeCaptchaQuestion();

		return <<<EOS
<p>Чрез <a href="#registerform" title="Към регистрационния формуляр">долния формуляр</a> можете да се регистрирате в <em>$this->sitename</em>.
<p>Ако вече сте се регистрирали, няма нужда да го правите още веднъж. Можете направо да <a href="$login">влезете</a>.</p>
<p>Можете да ползвате кирилица, когато въвеждате потребителското си име.</p>
<p>Като парола се опитайте да изберете нещо, което за вас да е лесно запомнящо се, а за останалите — невъзможно за разгадаване.</p>
<p>Попълването на полетата, обозначени със звездички, не е задължително.</p>

<form action="" method="post" id="registerform" style="width:33em; margin:1em auto" align="center">
		$returnto
		$attempt
	<table>
	<tr>
		<td class="fieldname-left"><label for="username">Потребителско име:</label></td>
		<td>$username</td>
	</tr><tr>
		<td class="fieldname-left"><label for="password">Парола:</label></td>
		<td>$password</td>
	</tr><tr>
		<td class="fieldname-left"><label for="passwordRe">Паролата още веднъж:</label></td>
		<td>$passwordRe</td>
	</tr><tr>
		<td class="fieldname-left"><label for="realname">Истинско име<a id="n1" name="n1" href="#nb1">*</a>:</label></td>
		<td>$realname</td>
	</tr><tr>
		<td class="fieldname-left"><label for="email">Е-поща<a id="n2" name="n2" href="#nb2">**</a>:</label></td>
		<td>$email</td>
	</tr><tr>
		<td colspan="2">
			$news
			<div class="extra">(Алтернативен начин да следите новото в библиотеката предлага страницата <a href="$historyLink">Новодобавено</a>)</div>
		</td>
	</tr><tr>
		<td colspan="2" align="center">$question</td>
	</tr><tr>
		<td colspan="2" align="center">$submit</td>
	</tr>
	</table>
</form>

<p><a id="nb1" name="nb1" href="#n1">*</a>, <a id="nb2" name="nb2" href="#n2">**</a>
Посочването на истинско име и валидна е-поща ще позволи по-доброто общуване между вас и библиотеката. Можете например да поискате нова парола, ако забравите сегашната си, или пък да се абонирате за месечен бюлетин. Адресът ви няма да се публикува на страниците.</p>
EOS;
	}
}
