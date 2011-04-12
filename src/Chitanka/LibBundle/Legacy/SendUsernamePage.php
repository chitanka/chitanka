<?php
namespace Chitanka\LibBundle\Legacy;

use Chitanka\LibBundle\Entity\User;

class SendUsernamePage extends MailPage {

	protected
		$action = 'sendUsername';


	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = 'Изпращане на потребителско име';
		$this->email = $this->request->value('email');
	}


	protected function processSubmission() {
		$key = array('email' => $this->email);
		$res = $this->db->select(DBT_USER, $key, 'username');
		$data = $this->db->fetchAssoc($res);
		if ( empty($data) ) {
			$this->addMessage("Не съществува потребител с електронна поща <strong>$this->email</strong>.", true);

			return $this->buildContent();
		}
		extract($data);
		$this->username = $username;
		$this->mailToName = $username;
		$this->mailToEmail = $this->email;
		$this->mailSubject = 'Напомняне за име от '.$this->sitename;

		$sendpass = $this->controller->generateUrl('request_password');
		$login = $this->controller->generateUrl('login');

		$this->mailSuccessMessage = "На адреса <strong>$this->email</strong> беше
			изпратено напомнящо писмо. Ако не се сещате и за паролата си,
			ползвайте функцията „<a href=\"$sendpass\">Изпращане на нова парола</a>“.
			Иначе можете спокойно да <a href=\"$login\">влезете</a>.";
		$this->mailFailureMessage = 'Изпращането на напомняне не сполучи.';

		return parent::processSubmission();
	}


	protected function makeForm() {
		$email = $this->out->textField('email', '', $this->email, 25, 255, 2);
		$submit = $this->out->submitButton('Изпращане на потребителското име', '', 3);

		return <<<EOS

<p>Е, на всекиго може да се случи да си забрави името. ;-) Няма страшно!
Ако в потребителските си данни сте посочили валидна електронна поща, сега
можете да поискате напомняне за името, с което сте се регистрирали в
<em>$this->sitename</em>.</p>
<p><br /></p>
<form action="" method="post">
<fieldset>
	<legend>Напомняне за име</legend>
	<label for="email">Електронна поща:</label>
	$email
	$submit
</fieldset>
</form>
EOS;
	}


	protected function makeMailMessage() {
		$passlink = $this->controller->generateUrl('request_password', array(), true);

		return <<<EOS
Здравейте!

Някой (най-вероятно вие) поиска да ви изпратим потребителското име, с което сте
се регистрирали в $this->sitename ($this->purl).
Ако все пак не сте били вие, можете да не обръщате внимание на това писмо.

Потребителското ви име е „{$this->username}“ (без кавичките).
Ако не се сещате и за паролата си, ползвайте функцията
„Изпращане на нова парола“ ($passlink).

$this->sitename

EOS;
	}

}
