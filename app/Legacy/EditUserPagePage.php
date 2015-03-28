<?php namespace App\Legacy;

use App\Util\String;

class EditUserPagePage extends UserPage {

	protected $action = 'editUserPage';
	private $save;
	private $preview;

	public function __construct($fields) {
		parent::__construct($fields);
		$this->save = $this->request->value('save');
		$this->preview = $this->request->value('preview');
	}

	protected function processSubmission() {
		if ( ! $this->userExists() ) {
			return;
		}

		$this->userpage = String::my_replace($this->userpage);
		if ( isset($this->preview) ) {
			$html = $this->makeHTML();
			$this->addMessage('Това е само предварителен преглед. Страницата все още не е съхранена.');

			return "\n<div id='previewbox'>\n$html\n</div>".$this->makeEditForm();
		}

		file_put_contents($this->filename, $this->userpage);
		$this->setDefaultTitle();

		$this->redirect = $this->controller->generateUrl('user_show', ['username' => $this->username]);

		return $this->makeEditOwnPageLink() . $this->makeHTML();
	}

	protected function buildContent() {
		if ( ! $this->userExists() ) {
			return;
		}

		$this->userpage = file_exists($this->filename) ? file_get_contents($this->filename) : '';

		return $this->makeEditForm();
	}

	protected function makeEditForm() {
		$this->title .= ' — Редактиране';
		$username = $this->out->hiddenField('username', $this->username);
		$userpage = $this->out->textarea('userpage', '', $this->userpage, 20, 80, 0, ['style'=>'width:95%']);

		$submit2 = $this->out->submitButton('Запис', '', 0, 'send');

		return <<<EOS

<p>За въвеждане на съдържанието се ползва <a href="http://wiki.chitanka.info/SFB">форматът SFB</a> — същият формат, в който са съхранени текстовете на библиотеката.</p>
<form action="" method="post"><div>
	$username
	<label for="userpage">Съдържание:</label><br>
	$userpage<br>
	$submit2
</div></form>
EOS;
	}

}
