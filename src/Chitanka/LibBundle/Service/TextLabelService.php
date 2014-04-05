<?php
namespace Chitanka\LibBundle\Service;

use Doctrine\ORM\EntityManager;
use Chitanka\LibBundle\Entity\Label;
use Chitanka\LibBundle\Entity\Text;
use Chitanka\LibBundle\Entity\TextLabel;
use Chitanka\LibBundle\Entity\TextLabelLog;
use Chitanka\LibBundle\Entity\User;

class TextLabelService {

	private $em;
	private $user;

	public function __construct(EntityManager $em, User $user) {
		$this->em = $em;
		$this->user = $user;
	}

	public function newTextLabel(Text $text) {
		$textLabel = new TextLabel;
		$textLabel->setText($text);
		return $textLabel;
	}

	public function addTextLabel(TextLabel $textLabel, Text $text) {
		// TODO Form::bind() overwrites the Text object with an id
		$textLabel->setText($text);
		$text->addLabel($textLabel->getLabel());
		$log = new TextLabelLog($text, $textLabel->getLabel(), $this->user, '+');
		$this->em->persist($text);
		$this->em->persist($log);
		$this->em->flush();
	}

	public function removeTextLabel(Text $text, Label $label) {
		$this->em->getConnection()->executeUpdate("DELETE FROM text_label WHERE text_id = {$text->getId()} AND label_id = {$label->getId()}");
		$log = new TextLabelLog($text, $label, $this->user, '-');
		$this->em->persist($log);
		$this->em->flush();
	}
}
