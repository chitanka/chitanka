<?php
namespace Chitanka\LibBundle\Legacy;

use Chitanka\LibBundle\Entity\User;

class LoginPage extends RegisterPage {

	const
		MAX_LOGIN_TRIES = 50;

	protected
		$action = 'login';


	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = 'Вход';
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
		$reglink = $this->controller->generateUrl('register');
		$sendname = $this->controller->generateUrl('request_username');
		$sendpass = $this->controller->generateUrl('request_password');

		if ( ! empty( $this->returnto ) ) {
			$this->returnto .= (strpos($this->returnto, '?') === false ? '?' : '&amp;') . 'cache=0';
		}
		$f_returnto = $this->out->hiddenField('returnto', $this->returnto);

		$username = $this->out->textField('username', '', $this->username, 25, 255, 2);
		$password = $this->out->passField('password', '', '', 25, 40, 3);
		$remember = $this->out->checkbox('remember', '', false, '', null, 3);
		$submit = $this->out->submitButton('Вход', '', 4);

		return <<<EOS

<p>Ако все още не сте се регистрирали, можете да го <a href="$reglink">направите</a> за секунди.</p>
<form action="" method="post" name="login_form">
	<fieldset style="width:38em; margin:1em auto" align="center">
		$f_returnto
	<legend>Влизане</legend>
	<table>
	<tr>
		<td class="fieldname-left"><label for="username">Потребителско име:</label></td>
		<td>$username <a href="$sendname">Забравено име</a></td>
	</tr><tr>
		<td class="fieldname-left"><label for="password">Парола:</label></td>
		<td>$password <a href="$sendpass">Забравена парола</a></td>
	</tr><tr>
		<td class="fieldname-left">$remember</td>
		<td><label for="remember">Запомняне на паролата</label></td>
	</tr><tr>
		<td colspan="2" style="text-align:center">$submit</td>
	</tr>
	</table>
	</fieldset>
</form>
EOS;
	}

}
