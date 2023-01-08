<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\HasLifecycleCallbacks
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
	 * @var ExternalSite
	 * @ORM\ManyToOne(targetEntity="ExternalSite")
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
	private $mediaType;

	public function getId() { return $this->id; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setSite($site) { $this->site = $site; }
	public function getSite() { return $this->site; }
	private function site() {
		return $this->site ?? ExternalSite::getDefault();
	}

	public function getSiteName() { return $this->site()->getName(); }

	public function setCode($code) {
		$this->code = $this->site()->extractMediaId($code);
	}
	public function getCode() { return $this->code; }

	public function setDescription($description) { $this->description = $description; }
	public function getDescription() { return $this->description; }

	public function setMediaType($type) { $this->mediaType = $type; }
	public function getMediaType() { return $this->mediaType; }

	public function getUrl() {
		return $this->site()->generateFullUrl($this->code);
	}

	public function __toString() {
		return $this->getSite() .' ('.$this->code.')';
	}

	/** @ORM\PrePersist */
	public function preInsert() {
		if (empty($this->mediaType)) {
			$this->setMediaType($this->site()->getMediaType());
		}
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
			'mediaType' => $this->mediaType,
		];
	}
}
