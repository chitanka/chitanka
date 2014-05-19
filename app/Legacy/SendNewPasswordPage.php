<?php namespace App\Legacy;

use App\Entity\User;

class SendNewPasswordPage extends MailPage {

	protected $action = 'sendNewPassword';
	protected $username;
	protected $newPassword;

	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = 'Изпращане на нова парола';
		$this->username = $this->request->value('username');
	}

	protected function processSubmission() {
		$userRepo = $this->controller->getUserRepository();
		$user = $userRepo->findByUsername($this->username);
		if (!$user) {
			$this->addMessage("Не съществува потребител с име <strong>$this->username</strong>.", true);

			return $this->buildContent();
		}

		if ($user->getEmail() == '') {
			$this->addMessage("За потребителя <strong>$this->username</strong> не е посочена електронна поща.", true);

			return $this->buildContent();
		}

		$this->mailToName = $this->username;
		$this->mailToEmail = $user->getEmail();

		$this->newPassword = User::randomPassword();
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
