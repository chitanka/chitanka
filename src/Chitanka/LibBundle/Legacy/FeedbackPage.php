<?php
namespace Chitanka\LibBundle\Legacy;

use Chitanka\LibBundle\Util\String;

class FeedbackPage extends MailPage {

	protected
		$action = 'feedback';


	public function __construct($fields) {
		parent::__construct($fields);
		$this->title = 'Връзка с екипа';

		$this->mailFromName = $this->name;
		$this->mailFromEmail = $this->email;

		$this->comment = $this->request->value('comment');
		$this->subject = $this->request->value('subject', "Обратна връзка от $this->sitename");
		$this->referer = $this->request->value('referer', $this->request->referer());
	}


	protected function processSubmission() {
		if ( empty($this->comment) ) {
			$this->addMessage('Е-е, това пък на какво прилича?!
				Без коментар не се приема! ;-)', true);
			return $this->buildContent();
		}
		if ( $this->user->isAnonymous() && String::isSpam($this->comment) ) {
			$this->addMessage('Коментарът ви е определен като спам. Вероятно съдържа прекалено много уеб адреси.', true);
			return $this->buildContent();
		}
		$this->mailSubject = $this->subject;
		$this->mailSuccessMessage = 'Съобщението ви беше изпратено. Благодаря ви!';
		$this->mailFailureMessage = 'Изглежда е станал някакъв фал при
			изпращането на съобщението ви. Ако желаете, пробвайте още веднъж.';
		return parent::processSubmission();
	}


	protected function makeSubmissionReturn() {
		if ( empty($this->referer) ) {
			return '';
		}
		$link = $this->out->link($this->referer, 'предишната страница');
		return "<p>Обратно към $link</p>";
	}


	protected function makeForm() {
		$referer = $this->out->hiddenField('referer', $this->referer);
		$subject = $this->out->textField('subject', '', $this->subject, 50);
		$name = $this->out->textField('name', '', $this->name, 50);
		$email = $this->out->textField('email', '', $this->email, 50);
		$comment = $this->out->textarea('comment', '', $this->comment, 10, 60);
		$submit = $this->out->submitButton('Пращане');
		$adminMail = $this->out->obfuscateEmail(ADMIN_EMAIL);
		return <<<EOS

<p>Имате богат избор от възможности, за да се свържете с администраторите на библиотеката. Можете да пишете в сайтовия форум, можете да пратите писмо по електронната поща ($adminMail), а можете да ползвате и долния формуляр. Посочете име и електронна поща, ако желаете да получите отговор.</p>
<p>Ползвайте кирилица, ако пишете на български!</p>
<p class="error">Тук не е мястото за търсене или поръчка на книги!</p>
<form action="" method="post">
<fieldset style="margin-top:1em; width:30em">
	<legend>Формулярче</legend>
	$referer
	<label for="comment">Коментар:</label>
	$comment
	<table summary="table for the layout"><tr>
		<td class="fieldname-left"><label for="subject">Тема:</label></td>
		<td>$subject</td>
	</tr><tr>
		<td class="fieldname-left"><label for="name">Име:</label></td>
		<td>$name</td>
	</tr><tr>
		<td class="fieldname-left"><label for="email">Е-поща:</label></td>
		<td>$email</td>
	</tr></table>
	<div>$submit</div>
</fieldset>
</form>
EOS;
	}


	protected function makeMailMessage() {
		return $this->comment;
	}

}
