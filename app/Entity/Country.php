<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Entity\CountryRepository")
 * @ORM\Cache(usage="READ_ONLY")
 * @ORM\Table(name="country")
 */
class Country implements \JsonSerializable {

	/**
	 * @var string
	 * @ORM\Column(type="string", length=3)
	 * @ORM\Id
	 * @Assert\NotBlank
	 */
	private $code;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=30)
	 * @Assert\NotBlank
	 */
	private $name;

	/**
	 * Number of authors from this category
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $nrOfAuthors = 0;

	/**
	 * Number of translators from this category
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $nrOfTranslators = 0;

	public function getCode() {
		return $this->code;
	}

	public function setCode($code) {
		$this->code = $code;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getNrOfAuthors() {
		return $this->nrOfAuthors;
	}

	public function setNrOfAuthors($nrOfAuthors) {
		$this->nrOfAuthors = $nrOfAuthors;
	}

	public function getNrOfTranslators() {
		return $this->nrOfTranslators;
	}

	public function setNrOfTranslators($nrOfTranslators) {
		$this->nrOfTranslators = $nrOfTranslators;
	}

	public function __toString() {
		return $this->name;
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'code' => $this->getCode(),
			'name' => $this->getName(),
			'nrOfAuthors' => $this->getNrOfAuthors(),
			'nrOfTranslators' => $this->getNrOfTranslators(),
		];
	}
}
