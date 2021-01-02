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

	const MEDIA_TYPE_HTML = 'html';
	const MEDIA_TYPE_AUDIO = 'audio';
	const MEDIA_TYPE_YOUTUBE = 'youtube';
	const MEDIA_TYPES = [self::MEDIA_TYPE_HTML, self::MEDIA_TYPE_AUDIO, self::MEDIA_TYPE_YOUTUBE];

	const MEDIAID_PLACEHOLDER = 'MEDIAID';

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=60)
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

	public static function getDefault() {
		static $default;
		return $default ?? $default = new self();
	}

	public function __construct() {
		$this->url = self::MEDIAID_PLACEHOLDER;
		$this->mediaType = self::MEDIA_TYPE_HTML;
	}

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

	public function generateFullUrl(string $mediaId): string {
		return str_replace(self::MEDIAID_PLACEHOLDER, $mediaId, $this->getUrl());
	}

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
