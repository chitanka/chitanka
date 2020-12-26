<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Table(name="text_link")
 */
class TextLink extends Entity implements \JsonSerializable {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var Text
	 * @ORM\ManyToOne(targetEntity="Text", inversedBy="links")
	 */
	private $text;

	/**
	 * @var BookSite
	 * @ORM\ManyToOne(targetEntity="BookSite")
	 */
	private $site;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255)
	 */
	private $code;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $description;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=30)
	 */
	private $type;

	public function getId() { return $this->id; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setSite($site) { $this->site = $site; }
	public function getSite() { return $this->site; }

	public function getSiteName() { return $this->site->getName(); }

	public function setCode($code) { $this->code = $code; }
	public function getCode() { return $this->code; }

	public function setDescription($description) { $this->description = $description; }
	public function getDescription() { return $this->description; }

	public function setType($type) { $this->type = $type; }
	public function getType() { return $this->type; }

	public function getUrl() {
		return str_replace('BOOKID', $this->code, $this->site->getUrl());
	}

	public function __toString() {
		return $this->getSite() .' ('.$this->code.')';
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'code' => $this->getCode(),
			'description' => $this->getDescription(),
			'type' => $this->type,
		];
	}
}
