<?php

namespace Chitanka\LibBundle\Legacy;

use Chitanka\LibBundle\Entity\Text;
use Chitanka\LibBundle\Util\Ary;
use Chitanka\LibBundle\Util\String;
use Chitanka\LibBundle\Validator\Constraints\NotSpamValidator;
use Chitanka\LibBundle\Validator\Constraints\NotSpam;

class SuggestDataPage extends MailPage {

	protected
		$FF_SUBACTION = 'object',
		$FF_TEXT_ID = 'id',
		$FF_INFO = 'info', $FF_NAME = 'name', $FF_EMAIL = 'email',
		$action = 'suggestData',
		$subactions = array(
			'orig_title' => '+оригинално заглавие',
			'orig_year' => '+година на написване или първа публикация',
			'translator' => '+преводач',
			'trans_year' => '+година на превод',
			'annotation' => 'Предложение за анотация'
		),
		$defSubaction = 'annotation',
		$work = null;


	public function __construct($fields) {
		parent::__construct($fields);
		$this->subaction = Ary::normKey(
			$this->request->value($this->FF_SUBACTION, $this->defSubaction, 1),
			$this->subactions, $this->defSubaction);
		$this->title = strtr($this->subactions[$this->subaction],
			array('+' => 'Информация за '));
		$this->textId = (int) $this->request->value($this->FF_TEXT_ID, 0, 2);
		$this->info = $this->request->value($this->FF_INFO);
		$this->name = $this->request->value($this->FF_NAME, $this->user->getUsername());
		$this->email = $this->request->value($this->FF_EMAIL, $this->user->getEmail());
		$this->initData();
	}


	protected function processSubmission() {
		if ( empty($this->info) ) {
			$this->addMessage('Не сте въвели никаква информация.', true);
			return $this->buildContent();
		}
		$notSpamValidator = new NotSpamValidator;
		if ( $this->user->isAnonymous() && !$notSpamValidator->validate($this->info, new NotSpam) ) {
			$this->addMessage('Съобщението ви е определено като спам. Вероятно съдържа прекалено много уеб адреси.', true);
			return $this->buildContent();
		}

		$this->mailToEmail = Setup::setting('work_email');

		$this->mailFromName = $this->name;
		$this->mailFromEmail = $this->email;

		$this->mailSubject = $this->title;
		$this->mailSuccessMessage = 'Съобщението ви беше изпратено. Благодаря ви!';
		$this->mailFailureMessage = 'Изглежда е станал някакъв фал при изпращането на съобщението ви. Ако желаете, пробвайте още веднъж.';
		return parent::processSubmission();
	}


	protected function makeSubmissionReturn() {
		return '<p>Обратно към „'.
			$this->makeSimpleTextLink($this->work->getTitle(), $this->textId, 1)
			.'“</p>';
	}


	protected function makeForm() {
		if ( empty($this->work) ) {
			return '';
		}
		$intro = $this->makeIntro();
		$info = $this->out->textarea($this->FF_INFO, '', $this->info, 15, 80);
		$name = $this->out->textField($this->FF_NAME, '', $this->name, 50);
		$email = $this->out->textField($this->FF_EMAIL, '', $this->email, 50);
		$submit = $this->out->submitButton('Пращане');
		return <<<EOS
$intro
<p>Посочването на име и електронна поща не е задължително.</p>
<form action="" method="post">
<fieldset style="margin-top:1em; width:30em">
	<table summary="table for the layout"><tr>
		<td class="fieldname-left"><label for="$this->FF_NAME">Име:</label></td>
		<td>$name</td>
	</tr><tr>
		<td class="fieldname-left"><label for="$this->FF_EMAIL">Е-поща:</label></td>
		<td>$email</td>
	</tr></table>
	<label for="$this->FF_INFO">Информация:</label><br />
	$info<br />
	$submit
</fieldset>
</form>
EOS;
	}


	protected function makeIntro() {
		$ta = '„'. $this->makeSimpleTextLink($this->work->getTitle(), $this->textId, 1) .'“'.
			$this->makeFromAuthorSuffix($this->work);
		switch ($this->subaction) {
		case 'orig_title':
			return "<p>Ако знаете какво е оригиналното заглавие на $ta, можете да го съобщите чрез следния формуляр, за да бъде въведено в базата от данни на библиотеката. Полезна е и всякаква друга допълнителна информация за произведението.</p>";
		case 'translator':
			return "<p>Ако знаете кой е превел $ta, можете да го съобщите чрез следния формуляр.</p>";
		case 'annotation':
			$commentUrl = $this->controller->generateUrl('text_comments', array('id' => $this->textId));
			return <<<EOS
<p>Чрез следния формуляр можете да предложите анотация на $ta. Ако просто искате да оставите коментар към произведението, ползвайте <a href="$commentUrl">страницата за читателски мнения</a>.</p>
<p><strong>Ако сте копирали анотацията, задължително посочете точния източник!</strong></p>
EOS;
		case 'orig_year':
			return "<p>Ако имате информация за годината на написване или първа публикация на $ta, можете да я съобщите чрез следния формуляр.</p>";
		case 'trans_year':
			return "<p>Ако имате информация за годината на превод на $ta, можете да я съобщите чрез следния формуляр.</p>";
		}
	}


	protected function makeMailMessage() {
		$title = $this->work->getTitle();
		return <<<EOS
Произведение: „{$title}“

http://chitanka.info/admin/text/$this->textId/edit

$this->info
EOS;
	}


	protected function initData() {
		$this->work = $this->controller->getRepository('Text')->find($this->textId);
		if ( empty($this->work) ) {
			$this->addMessage("Не съществува текст с номер <strong>$this->textId</strong>.", true);
		}
	}
}
