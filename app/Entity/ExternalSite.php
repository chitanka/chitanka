<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Table(name="external_site")
 * @UniqueEntity(fields="name")
 */
class ExternalSite extends Entity implements \JsonSerializable {

	const MEDIA_TYPES = ['html', 'audio', 'youtube'];

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=50, unique=true)
	 */
	private $name = '';

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100)
	 */
	private $url;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=30)
	 */
	private $mediaType;

	public function __toString() {
		return $this->name ?: $this->url;
	}

	public function getId() { return $this->id; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setUrl($url) { $this->url = $url; }
	public function getUrl() { return $this->url; }

	public function setMediaType($mediaType) { $this->mediaType = $mediaType; }
	public function getMediaType() { return $this->mediaType; }

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'url' => $this->getUrl(),
			'mediaType' => $this->mediaType,
		];
	}
}
