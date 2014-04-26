<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="license")
 * @UniqueEntity(fields="code")
 */
class License extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var string $code
	 * @ORM\Column(type="string", length=20, unique=true)
	 */
	private $code;

	/**
	 * @var string $name
	 * @ORM\Column(type="string", length=15)
	 */
	private $name = '';

	/**
	 * @var string $fullname
	 * @ORM\Column(type="string", length=255)
	 */
	private $fullname;

	/**
	 * @var boolean $free
	 * @ORM\Column(type="boolean")
	 */
	private $free;

	/**
	 * @var boolean $copyright
	 * @ORM\Column(type="boolean")
	 */
	private $copyright;

	/**
	 * @var string $uri
	 * @ORM\Column(type="string", length=255)
	 */
	private $uri;

	/**
	 * Get id
	 *
	 * @return integer $id
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set code
	 *
	 * @param string $code
	 */
	public function setCode($code) {
		$this->code = $code;
	}

	/**
	 * Get code
	 *
	 * @return string $code
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * Set name
	 *
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Get name
	 *
	 * @return string $name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Set fullname
	 *
	 * @param string $fullname
	 */
	public function setFullname($fullname) {
		$this->fullname = $fullname;
	}

	/**
	 * Get fullname
	 *
	 * @return string $fullname
	 */
	public function getFullname() {
		return $this->fullname;
	}

	/**
	 * Set free
	 *
	 * @param boolean $free
	 */
	public function setFree($free) {
		$this->free = $free;
	}

	/**
	 * Get free
	 *
	 * @return boolean $free
	 */
	public function getFree() {
		return $this->free;
	}

	/**
	 * Set copyright
	 *
	 * @param boolean $copyright
	 */
	public function setCopyright($copyright) {
		$this->copyright = $copyright;
	}

	/**
	 * Get copyright
	 *
	 * @return boolean $copyright
	 */
	public function getCopyright() {
		return $this->copyright;
	}

	/**
	 * Set uri
	 *
	 * @param string $uri
	 */
	public function setUri($uri) {
		$this->uri = $uri;
	}

	/**
	 * Get uri
	 *
	 * @return string $uri
	 */
	public function getUri() {
		return $this->uri;
	}

	public function __toString() {
		return $this->name;
	}
}
