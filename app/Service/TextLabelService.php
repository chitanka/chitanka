<?php namespace App\Service;

use App\Entity\Label;
use App\Entity\Text;
use App\Entity\TextLabel;
use App\Entity\TextLabelLog;
use App\Entity\TextLabelLogRepository;
use App\Entity\User;

class TextLabelService {

	private $repo;
	private $user;

	public function __construct(TextLabelLogRepository $repo, User $user) {
		$this->repo = $repo;
		$this->user = $user;
	}

	public function newTextLabel(Text $text) {
		$textLabel = new TextLabel;
		$textLabel->setText($text);
		return $textLabel;
	}

	/**
	 * @param TextLabel $textLabel
	 * @param Text $text
	 */
	public function addTextLabel(TextLabel $textLabel, Text $text) {
		$textLabel->setText($text);
		$text->addLabel($textLabel->getLabel());
		$log = new TextLabelLog($text, $textLabel->getLabel(), $this->user, '+');
		$this->repo->save($text);
		$this->repo->save($log);
	}

	public function removeTextLabel(Text $text, Label $label) {
		$this->repo->execute("DELETE FROM text_label WHERE text_id = {$text->getId()} AND label_id = {$label->getId()}");
		$log = new TextLabelLog($text, $label, $this->user, '-');
		$this->repo->save($log);
	}
}
