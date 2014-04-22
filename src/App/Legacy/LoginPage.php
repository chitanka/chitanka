<?php
namespace App\Legacy;

use App\Entity\User;

class LoginPage extends RegisterPage {

	const
		MAX_LOGIN_TRIES = 50;

	protected
		$action = 'login';


	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = "Вход в $this->sitename";
		$this->remember = (int) $this->request->checkbox('remember');
		$this->message = '';
	}


	protected function processSubmission() {
		$err = $this->validateInput();
		if ( !empty($err) ) {
			$this->addMessage($err, true);

			return $this->buildContent();
		}
		$user = $this->controller->getRepository('User')->findOneBy(array('username' => $this->username));
		if ( ! $user) {
			$this->addMessage("Не съществува потребител с име <strong>$this->username</strong>.", true );

			return $this->buildContent();
		}

		if ( ! $user->validatePassword($this->password) ) { // no match
			if ( ! $user->validateNewPassword($this->password) ) { // no match
				if ( $user->getLoginTries() >= self::MAX_LOGIN_TRIES ) {
					$this->redirect = $this->controller->generateUrl('homepage');
					return
						'<div class="error">'
						.'<p>Направени са повече от '. self::MAX_LOGIN_TRIES .' неуспешни опита за влизане в библиотеката с името <strong>'.$this->username.'</strong>, затова сметката е била блокирана.</p>'
						. sprintf('<p>Ползвайте страницата „<a href="%s">Изпращане на нова парола</a>“, за да получите нова парола за достъп, или се свържете с администратора на библиотеката.</p>', $this->controller->generateUrl('request_password'));
				}
				$this->addMessage('Въвели сте грешна парола.', true);
				$user->incLoginTries();

				return $this->buildContent();
			}
			$user->activateNewPassword();
		}

		$user->setPassword($this->password); // update with the new algorithm
		$user->login($this->remember);

		$em = $this->controller->getEntityManager();
		$em->persist($user);
		$em->flush();

		$this->controller->setUser($user);

		if (empty($this->returnto)) {
			$this->redirect = $this->controller->generateUrl('homepage');
		} else {
			$this->redirect = $this->returnto;
		}

		return "Влязохте в <em>$this->sitename</em> като $this->username.";
	}


	protected function validateInput() {
		if ( empty($this->username) ) {
			return 'Не сте въвели потребителско име.';
		}
		if ( empty($this->password) ) {
			return 'Не сте въвели парола.';
		}
		return '';
	}


	protected function buildContent() {
		return $this->makeLoginForm();
	}


	protected function makeLoginForm() {
		$loginlink = $this->controller->generateUrl('login');
		$reglink = $this->controller->generateUrl('register');
		$sendname = $this->controller->generateUrl('request_username');
		$sendpass = $this->controller->generateUrl('request_password');

		if ( ! empty( $this->returnto ) ) {
			$this->returnto .= (strpos($this->returnto, '?') === false ? '?' : '&amp;') . 'cache=0';
		}
		$f_returnto = $this->out->hiddenField('returnto', $this->returnto);

		return <<<EOS

<style>
.form-signin {
	margin: 1em auto 3em;
	max-width: 25em;
}
.form-signin .form-control {
	height: auto;
	padding: 10px;
}
.form-signin .input-group-addon .fa {
	width: .8em;
}
</style>
<form action="$loginlink" method="post" class="form-signin" role="form">
	$f_returnto
	<div class="input-group">
		<span class="input-group-addon"><span class="fa fa-user"></span></span>
		<label for="username" class="sr-only">Потребителско име</label>
		<input type="text" class="form-control" id="username" name="username" placeholder="Потребителско име" value="{$this->username}" required autofocus>
		<span class="input-group-addon"><a href="$sendname" title="Заявка за забравено име"><span class="fa fa-question"></span></a></span>
	</div>
	<div class="input-group">
		<span class="input-group-addon"><span class="fa fa-key"></span></span>
		<label for="username" class="sr-only">Парола</label>
		<input type="password" class="form-control" id="password" name="password" placeholder="Парола" required>
		<span class="input-group-addon"><a href="$sendpass" title="Заявка за забравена парола"><span class="fa fa-question"></span></a></span>
	</div>
	<div class="checkbox">
		<label>
			<input type="checkbox" name="remember"> Запомняне за един месец
		</label>
	</div>
	<button class="btn btn-lg btn-primary btn-block" type="submit">Вход</button>
</form>

<div class="alert alert-info media">
	<div class="pull-left">
		<span class="fa fa-2x fa-frown-o"></span>
	</div>
	<div class="media-body">
		Забравихте си входните данни ли? Няма страшно. Подайте <a href="$sendname" title="Заявка за забравено име" class="btn btn-default">заявка за забравено име</a> или <a href="$sendpass" title="Заявка за забравена парола" class="btn btn-default">парола</a>.
	</div>
</div>
<div class="alert alert-info media">
	<div class="pull-left">
		<span class="fa fa-2x fa-user"></span>
	</div>
	<div class="media-body">
		Можете да се <a href="$reglink" class="btn btn-default">регистрирате</a> за секунди, ако все още не сте го направили.
	</div>
</div>

EOS;
	}

}
