<?php namespace App\Entity;

class JuxtaposedText {

	/** @var Text */
	private $thisText;

	/** @var Text */
	private $otherText;

	/** @var Text The text which is visible to the outside */
	private $shownText;

	public function __construct(Text $thisText, Text $otherText) {
		$this->thisText = $thisText;
		$this->otherText = $otherText;
		$this->shownText = $this->otherText;
	}

	public function getId() {
		return implode(',', [$this->thisText->getId(), $this->otherText->getId()]);
	}

	public function getType() { return $this->shownText->getType(); }
	public function getRating() { return $this->shownText->getRating(); }
	public function getVotes() { return $this->shownText->getVotes(); }
	public function getSlug() { return $this->shownText->getSlug(); }
	public function getHeadlevel() { return $this->shownText->getHeadlevel(); }
	public function getNote() { return $this->shownText->getNote(); }
	public function getTitle() { return $this->shownText->getTitle(); }
	public function getLang() { return $this->shownText->getLang(); }
	public function getAuthors() { return $this->shownText->getAuthors(); }

}
