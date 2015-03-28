<?php namespace App\Legacy;

use App\Entity\User;

class RegisterPage extends Page {

	protected $action = 'register';
	protected $returnto;

	protected $username;
	protected $password;
	protected $passwordRe;
	protected $realname;
	protected $email;
	protected $news;

	private $invalidReferers = ['login', 'logout', 'register', 'sendNewPassword'];
	private $attempt;

	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = "Регистрация в $this->sitename";
		$this->attempt = (int) $this->request->value('attempt', 1);
		$this->username = trim($this->request->value('username', ''));
		$this->password = trim($this->request->value('password', ''));
		$this->passwordRe = trim($this->request->value('passwordRe', ''));
		$this->realname = trim($this->request->value('realname', ''));
		$this->email = trim($this->request->value('email', ''));
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
		if ($this->email) {
			$user->setIsEmailValid(true);
			$user->setAllowemail(true);
		}
		$user->setNews((bool) $this->news);

		$this->controller->em()->getUserRepository()->save($user);

		$this->addMessage("Регистрирахте се в <em>$this->sitename</em> като $this->username.");

		return '';
	}

	private function validateInput() {
		if ( ! $this->verifyCaptchaAnswer() ) {
			return 'Не сте отговорили правилно на въпроса уловка.';
		}

		foreach (['username', 'password', 'passwordRe'] as $nonEmptyField) {
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
		if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
			return 'Въведеният адрес за електронна поща е невалиден.';
		}
		return '';
	}

	protected function isValidPassword() {
		return strcmp($this->password, $this->passwordRe) === 0;
	}

	private function userExists() {
		$key = ['username' => $this->username];
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

		$emailKey = ['email' => $this->email];
		if ( !is_null($notUsername) ) {
			$emailKey['username'] = ['!=', $notUsername];
		}
		if ( $this->db->exists(DBT_USER, $emailKey) ) {
			$this->addMessage("Пощенският адрес <strong>{$this->email}</strong> вече се ползва от друг потребител.", true);
			$this->addMessage("Ако сте забравили потребителското си име, можете <a href=\"{$this->controller->generateUrl('request_username')}\">да поискате напомняне за него</a>.");
			return true;
		}

		return false;
	}

	protected function buildContent() {
		return <<<EOS
<p>Ако вече сте се регистрирали, няма нужда да го правите още веднъж. Можете направо да <a href="{$this->controller->generateUrl('login')}" class="btn btn-default">влезете</a>.</p>
<ul class="fa-ul">
<li><span class="fa-li fa fa-user"></span> Позволено е използването на кирилица, когато въвеждате потребителското си име.</li>
<li><span class="fa-li fa fa-key"></span> Като парола се опитайте да изберете нещо, което за вас да е лесно запомнящо се, а за останалите — невъзможно за разгадаване.</li>
<li><span class="fa-li fa fa-envelope"></span> Посочването на истинско име и валидна електронна поща не е задължително, но наличието им ще позволи по-доброто общуване между вас и библиотеката. Можете например да поискате нова парола, ако забравите сегашната си, или пък да се абонирате за месечния бюлетин. Адресът на пощата ви няма да се вижда от останалите потребители.</li>
</ul>
<style>
.form-register {
	margin: 1em auto 3em;
	max-width: 35em;
}
.form-register .form-control {
	height: auto;
	padding: 10px;
}
.form-register .input-group-addon .fa {
	width: 1em;
}
.form-register .input-group-addon label {
	width: 15em;
	text-align: left;
}
.form-register .checkbox {
	padding-top: 0;
}
</style>
<form action="{$this->controller->generateUrl('register')}" method="post" class="form-horizontal form-register" role="form">
		{$this->out->hiddenField('returnto', $this->returnto)}
		{$this->out->hiddenField('attempt', $this->attempt)}
	<div class="input-group">
		<span class="input-group-addon"><label for="username"><span class="fa fa-user"></span> Потребителско име</label></span>
		<input type="text" class="form-control" id="username" name="username" value="{$this->username}" required autofocus>
	</div>
	<div class="input-group">
		<span class="input-group-addon"><label for="password"><span class="fa fa-key"></span> Парола</label></span>
		<input type="password" class="form-control" id="password" name="password" value="{$this->password}" required>
	</div>
	<div class="input-group">
		<span class="input-group-addon"><label for="passwordRe"><span class="fa fa-key"></span> Паролата още веднъж</label></span>
		<input type="password" class="form-control" id="passwordRe" name="passwordRe" value="{$this->passwordRe}" required>
	</div>
	<div class="input-group">
		<span class="input-group-addon"><label for="realname"><span class="fa fa-envelope"></span> Истинско име</label></span>
		<input type="text" class="form-control" id="realname" name="realname" value="{$this->realname}">
	</div>
	<div class="input-group">
		<span class="input-group-addon"><label for="email"><span class="fa fa-envelope"></span> Е-поща</label></span>
		<input type="email" class="form-control" id="email" name="email" value="{$this->email}">
	</div>
	<div class="form-control">
		<div class="checkbox">
			<label>
				<input type="checkbox" name="news"> Получаване на месечен бюлетин
			</label>
		</div>
		<div class="help-block">Алтернативен начин да следите новото в библиотеката предлага страницата <a href="{$this->controller->generateUrl('new')}">Новодобавено</a>.</div>
	</div>
	<div class="form-group">
		{$this->makeCaptchaQuestion()}
	</div>
	<button class="btn btn-lg btn-primary btn-block" type="submit">Регистриране</button>
</form>
</p>
EOS;
	}
}
