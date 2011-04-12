<?php
namespace Chitanka\LibBundle\Legacy;

use Chitanka\LibBundle\Entity\User;

class SettingsPage extends RegisterPage {

	protected
		$action = 'settings',
		$canChangeUsername = false,
		$optKeys = array('skin', 'nav'),
		$defEcnt = 10,
		$nonEmptyFields = array();


	public function __construct($fields) {
		parent::__construct($fields);

		$this->title = 'Настройки';
		$this->userId = $this->user->getId();
		$this->allowemail = $this->request->checkbox('allowemail');
		foreach ($this->optKeys as $key) {
			$this->opts[$key] = $this->request->value($key, User::$defOptions[$key]);
		}

		$this->tabindex = 2;
	}


	protected function processSubmission() {
		return $this->processRegUserRequest();
	}


	protected function isValidPassword() {
		// sometimes browsers automaticaly fill the first password field
		// so the user does NOT want to change it
		if ( $this->user->validatePassword($this->password) ) {
			return true;
		}

		return parent::isValidPassword();
	}


	protected function processRegUserRequest()
	{
		$err = $this->validateInput();
		$this->attempt++;
		if ( !empty($err) ) {
			$this->addMessage($err, true);
			return $this->makeRegUserForm();
		}

		if ( $this->emailExists($this->user->getUsername()) ) {
			return $this->makeRegUserForm();
		}

		$user = $this->controller->getRepository('user')->find($this->user->getId());
		$user->setRealname($this->realname);
		$user->setEmail($this->email);
		$user->setAllowemail((int) $this->allowemail);
		$user->setNews((int) $this->news);
		$user->setOpts($this->makeOptionsOutput());

		if ( !empty($this->password) && !empty($this->passwordRe) ) { // change password
			$user->setPassword($this->password);
		}

		$em = $this->controller->getEntityManager();
		$em->persist($user);
		$em->flush();

		$this->addMessage("Данните ви бяха променени.");

		$this->user = $user;
		$this->controller->setUser($user);
		$this->user->updateSession();

		return $this->makeRegUserForm();
	}


	protected function buildContent() {
		$this->initRegUserData();

		return $this->makeRegUserForm();
	}


	protected function makeRegUserForm() {
		$formBegin = $this->makeFormBegin();
		$attempt = $this->out->hiddenField('attempt', $this->attempt);
		$username = $this->canChangeUsername
			? $this->out->textField('username', '', $this->username, 25, 60, $this->tabindex++)
			: "<span id='username'>".$this->user->getUsername()."</span>";
		$password = $this->out->passField('password', '', '', 25, 40, $this->tabindex++);
		$passwordRe = $this->out->passField('passwordRe', '', '', 25, 40, $this->tabindex++);
		$realname = $this->out->textField('realname', '', $this->realname, 25, 60, $this->tabindex++);
		$email = $this->out->textField('email', '', $this->email, 25, 60, $this->tabindex++);
		$allowemail = $this->out->checkbox('allowemail', '', $this->allowemail,
			'Разрешаване на писма от другите потребители', null, $this->tabindex++);
		$common = $this->makeCommonInput();
		$news = $this->out->checkbox('news', '', $this->news,
			'Получаване на месечен бюлетин', null, $this->tabindex++);
		$formEnd = $this->makeFormEnd();
		$historyLink = $this->controller->generateUrl('new');

		return <<<EOS

$formBegin
	$attempt
	<legend>Данни и настройки</legend>
	<table>
	<tr>
		<td class="fieldname-left"><label for="username">Потребителско име:</label></td>
		<td>$username</td>
	</tr><tr>
		<td class="fieldname-left"><label for="password">Нова парола<a id="nb1" href="#n1">*</a>:</label></td>
		<td>$password</td>
	</tr><tr>
		<td class="fieldname-left"><label for="passwordRe">Новата парола още веднъж:</label></td>
		<td>$passwordRe</td>
	</tr><tr>
		<td colspan="2"><a id="n1" href="#nb1">*↑</a> <em>Нова парола</em> — въведете нова парола само ако искате да смените сегашната си.</td>
	</tr><tr>
		<td class="fieldname-left"><label for="realname">Истинско име:</label></td>
		<td>$realname</td>
	</tr><tr>
		<td class="fieldname-left"><label for="email">Е-поща:</label></td>
		<td>$email</td>
	</tr><tr>
		<td colspan="2">
			$allowemail
		</td>
	</tr>$common
	<tr>
		<td colspan="2">
			$news
			<div class="extra">(Алтернативен начин да следите новото в библиотеката предлага страницата <a href="$historyLink">Новодобавено</a>)</div>
		</td>
	</tr>
$formEnd
EOS;
	}


	protected function makeFormBegin() {
		return <<<EOS
<form action="" method="post">
<fieldset style="width: 35em; margin:1em auto" align="center">
EOS;
	}

	protected function makeFormEnd() {
		$submit = $this->out->submitButton('Запис', '', $this->tabindex++);
		return <<<EOS
	<tr>
		<td colspan="2" align="center">$submit</td>
	</tr>
	</table>
</fieldset>
</form>
EOS;
	}


	protected function makeCommonInput() {
		$skin = $this->makeSkinInput($this->tabindex++);
		$nav = $this->makeNavPosInput($this->tabindex++);

		return <<<EOS

	<tr>
		<td class="fieldname-left"><label for="skin">Облик:</label></td>
		<td>$skin</td>
	</tr><tr>
		<td class="fieldname-left"><label for="nav">Навигация:</label></td>
		<td>$nav</td>
	</tr>
EOS;
	}


	protected function makeSkinInput($tabindex) {
		return $this->out->selectBox('skin', '', Setup::setting('skins'),
			$this->opts['skin'], $tabindex,
			array('onchange'=>'skin=this.value; changeStyleSheet()'));
	}


	protected function makeNavPosInput($tabindex) {
		return $this->out->selectBox('nav', '', Setup::setting('navpos'),
			$this->opts['nav'], $tabindex,
			array('onchange'=>'nav=this.value; changeStyleSheet()'));
	}


	protected function makeOptionsOutput( $with_page_fields = true ) {
		//$opts = array_merge( $this->user->options(), $this->opts );
		$opts = $this->opts;
		if ( ! $with_page_fields ) {
			foreach ( $opts as $k => $_ ) {
				if ( strpos( $k, 'p_' ) === 0 ) {
					unset( $opts[$k] );
				}
			}
		}

		return $opts;
	}


	protected function initRegUserData() {
		$this->username = $this->user->getUsername();
		$this->password = $this->user->getPassword();
		$this->realname = $this->user->getRealname();
		$this->email = $this->user->getEmail();

		$this->opts = array_merge($this->opts, $this->user->getOpts());
		$this->allowemail = $this->user->getAllowemail();
		$this->news = $this->user->getNews();
	}

}
