<?php namespace App\Legacy;

use App\Entity\User;

class SettingsPage extends RegisterPage {

	protected
		$action = 'settings',
		$canChangeUsername = false,
		$optKeys = array('skin', 'nav', 'css', 'js'),
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

	protected function processRegUserRequest() {
		$err = $this->validateInput();
		$this->attempt++;
		if ( !empty($err) ) {
			$this->addMessage($err, true);
			return $this->makeRegUserForm();
		}

		if ( $this->emailExists($this->user->getUsername()) ) {
			return $this->makeRegUserForm();
		}

		$user = $this->controller->em()->getUserRepository()->find($this->user->getId());
		$user->setRealname($this->realname);
		$user->setEmail($this->email);
		$user->setAllowemail((int) $this->allowemail);
		$user->setNews((int) $this->news);
		$user->setOpts($this->makeOptionsOutput());

		if ( !empty($this->password) && !empty($this->passwordRe) ) { // change password
			$user->setPassword($this->password);
		}

		$em = $this->controller->em();
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
		$username = $this->canChangeUsername
			? $this->out->textField('username', '', $this->username, 25, 60, $this->tabindex++, '', array('class' => 'form-control'))
			: '<span id="username" class="form-control">'.$this->user->getUsername()."</span>";
		$password = $this->out->passField('password', '', '', 25, 40, $this->tabindex++, array('class' => 'form-control'));
		$passwordRe = $this->out->passField('passwordRe', '', '', 25, 40, $this->tabindex++, array('class' => 'form-control'));
		$realname = $this->out->textField('realname', '', $this->realname, 25, 60, $this->tabindex++, '', array('class' => 'form-control'));
		$email = $this->out->textField('email', '', $this->email, 25, 60, $this->tabindex++, '', array('class' => 'form-control'));
		$allowemail = $this->out->checkbox('allowemail', '', $this->allowemail, '', null, $this->tabindex++);
		$common = $this->makeCommonInput();
		$customInput = $this->makeCustomInput();
		$news = $this->out->checkbox('news', '', $this->news, '', null, $this->tabindex++);
		$historyLink = $this->controller->generateUrl('new');

		return <<<EOS

<form action="" method="post" class="form-horizontal" role="form">
	<input type="hidden" name="attempt" value="$this->attempt">
	<div class="form-group">
		<label for="username" class="col-sm-4 control-label">Потребителско име:</label>
		<div class="col-sm-8">
			$username
		</div>
	</div>
	<div class="form-group">
		<label for="password" class="col-sm-4 control-label">Нова парола<a id="nb1" href="#n1">*</a>:</label>
		<div class="col-sm-8">
			$password
			<span class="help-block">Въведете нова парола само ако искате да смените сегашната си.</span>
		</div>
		<label for="passwordRe" class="col-sm-4 control-label">Новата парола още веднъж:</label>
		<div class="col-sm-8">
			$passwordRe
		</div>
	</div>
	<div class="form-group">
		<label for="realname" class="col-sm-4 control-label">Истинско име:</label>
		<div class="col-sm-8">
			$realname
		</div>
	</div>
	<div class="form-group">
		<label for="email" class="col-sm-4 control-label">Е-поща:</label>
		<div class="col-sm-8">
			$email
		</div>
		<div class="col-sm-offset-4 col-sm-8">
			<div class="checkbox">
				<label>
					$allowemail Разрешаване на писма от другите потребители
				</label>
			</div>
		</div>
	</div>
	$common
	<div class="form-group">
		<div class="col-sm-offset-4 col-sm-8">
			<div class="checkbox">
				<label>
					$news Получаване на месечен бюлетин
				</label>
			</div>
			<span class="help-block">Алтернативен начин да следите новото в библиотеката предлага страницата <a href="$historyLink">Новодобавено</a>.</span>
		</div>
	</div>
	$customInput

	<div class="form-group">
		<div class="col-sm-offset-4 col-sm-8">
			{$this->out->submitButton('Запис', '', $this->tabindex++, false, array('class' => 'btn btn-primary'))}
		</div>
	</div>
</form>
EOS;
	}

	protected function makeCommonInput() {
		$skin = $this->makeSkinInput($this->tabindex++);
		$nav = $this->makeNavPosInput($this->tabindex++);

		return <<<EOS
	<div class="form-group">
		<label for="skin" class="col-sm-4 control-label">Облик:</label>
		<div class="col-sm-8">
			$skin
		</div>
	</div>
	<div class="form-group">
		<label for="nav" class="col-sm-4 control-label">Навигация:</label>
		<div class="col-sm-8">
			$nav
		</div>
	</div>
EOS;
	}

	protected function makeSkinInput($tabindex) {
		return $this->out->selectBox('skin', '', Setup::setting('skins'),
			$this->opts['skin'], $tabindex,
			array('class' => 'form-control', 'onchange' => 'skin=this.value; changeStyleSheet()'));
	}

	protected function makeNavPosInput($tabindex) {
		return $this->out->selectBox('nav', '', Setup::setting('navpos'),
			$this->opts['nav'], $tabindex,
			array('class' => 'form-control', 'onchange' => 'nav=this.value; changeStyleSheet()'));
	}

	protected function makeCustomInput() {
		$inputs = '';
		$inputs .= '<div class="form-group">';
		$files = $this->container->getParameter('user_css');
		foreach ($files as $file => $title) {
			$inputs .= sprintf(<<<HTML
		<div class="col-sm-offset-4 col-sm-8">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="css[%s]" value="%s" %s> %s
				</label>
			</div>
		</div>
HTML
				,
				$file,
				$file,
				(isset($this->opts['css'][$file]) ? 'checked="checked"' : ''),
				$title);
		}
		$files = $this->container->getParameter('user_js');
		foreach ($files as $file => $title) {
			$inputs .= sprintf(<<<HTML
		<div class="col-sm-offset-4 col-sm-8">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="js[%s]" value="%s" %s> %s
				</label>
			</div>
		</div>
HTML
				,
				$file,
				$file,
				(isset($this->opts['js'][$file]) ? 'checked="checked"' : ''),
				$title);
		}
		$inputs .= '</div>';

		$inputs .= '<div class="form-group">';
		$cssCustomValue = isset($this->opts['css']['custom']) ? htmlspecialchars($this->opts['css']['custom']) : '';
		$inputs .= <<<HTML
		<label for="css_custom" class="col-sm-4 control-label">Собствени стилове:</label>
		<div class="col-sm-8">
			<input type="text" id="css_custom" class="form-control" name="css[custom]" value="$cssCustomValue" placeholder="http://mydomain.info/chitanka.css">
		</div>
HTML;
		$jsCustomValue = isset($this->opts['js']['custom']) ? htmlspecialchars($this->opts['js']['custom']) : '';
		$inputs .= <<<HTML
		<label for="js_custom" class="col-sm-4 control-label">Собствени скриптове:</label>
		<div class="col-sm-8">
			<input type="text" id="js_custom" class="form-control" name="js[custom]" value="$jsCustomValue" placeholder="http://mydomain.info/chitanka.js">
		</div>
HTML;
		$inputs .= '</div>';

		return $inputs;
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
