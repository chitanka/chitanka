<?php
namespace Chitanka\LibBundle\Legacy;

use Chitanka\LibBundle\Entity\User;

class SendNewPasswordPage extends MailPage {

	protected
		$action = 'sendNewPassword';


	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = 'Изпращане на нова парола';
		$this->username = $this->request->value('username');
	}


	protected function processSubmission() {
		$key = array('username' => $this->username);
		$res = $this->db->select(DBT_USER, $key, 'email');

		$data = $this->db->fetchAssoc($res);
		if ( empty($data) ) {
			$this->addMessage("Не съществува потребител с име <strong>$this->username</strong>.", true);

			return $this->buildContent();
		}

		extract($data);
		if ( empty($email) ) {
			$this->addMessage("За потребителя <strong>$this->username</strong> не е посочена електронна поща.", true);

			return $this->buildContent();
		}

		$this->mailToName = $this->username;
		$this->mailToEmail = $email;

		$this->newPassword = User::randomPassword();
		$user = $this->controller->getRepository('User')->findOneBy(array('username' => $this->username));
		$user->setNewpassword($this->newPassword);
		$em = $this->controller->getEntityManager();
		$em->persist($user);
		$em->flush();

		$this->mailSubject = "Нова парола за $this->sitename";
		$loginurl = $this->controller->generateUrl('login');
		$this->mailSuccessMessage = "Нова парола беше изпратена на електронната поща на <strong>$this->username</strong>. Моля, <a href=\"$loginurl\">влезте отново</a>, след като я получите.";
		$this->mailFailureMessage = 'Изпращането на новата парола не сполучи.';

		return parent::processSubmission();
	}

	protected function makeForm() {
		$username = $this->out->textField('username', '', $this->username, 25, 255, 2);
		$submit = $this->out->submitButton('Изпращане на нова парола', '', 3);

		return <<<EOS

<p>Чрез долния формуляр можете да поискате нова парола за влизане в <em>$this->sitename</em>, ако сте забравили сегашната си. Такава обаче може да ви бъде изпратена само ако сте посочили валидна електронна поща в потребителските си данни.</p>

<form action="" method="post">
<fieldset>
	<legend>Нова парола</legend>
	<label for="username">Потребителско име:</label>
	$username
	$submit
</fieldset>
</form>
EOS;
	}


	protected function makeMailMessage() {
		return <<<EOS
Здравейте!

Някой (най-вероятно вие) поиска да ви изпратим нова парола за
влизане в $this->sitename (http://chitanka.info). Ако все пак
не сте били вие, можете да не обръщате внимание на това писмо и да
продължите да ползвате сегашната си парола.

Новата ви парола е {$this->newPassword}

След като влезете с нея в $this->sitename, е препоръчително да я
смените с някоя по-лесно запомняща се, за да не се налага пак да
прибягвате до функцията „Изпращане на нова парола“. ;-)

$this->sitename

EOS;
	}

}
