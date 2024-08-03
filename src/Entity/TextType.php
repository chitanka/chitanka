<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Persistence\TextTypeRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Table(name="text_type")
 */
class TextType implements \JsonSerializable {

	/**
	 * @var string
	 * @ORM\Column(type="string", length=14)
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
	 * @var string
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $description = '';

	/**
	 * Number of texts having this label
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $nrOfTexts = 0;

	public function getId() { return $this->code; }
	public function getCode() { return $this->code; }
	public function setCode($code) { $this->code = $code; }

	public function getName() { return $this->name; }
	public function setName($name) { $this->name = $name; }

	public function setDescription($description) { $this->description = $description; }
	public function getDescription() { return $this->description; }

	public function setNrOfTexts($nrOfTexts) { $this->nrOfTexts = $nrOfTexts; }
	public function getNrOfTexts() { return $this->nrOfTexts; }
	public function incNrOfTexts($value = 1) {
		$this->nrOfTexts += $value;
	}


	public function __toString() {
		return $this->name;
	}

	public function is($code) {
		return $this->code === $code;
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'code' => $this->code,
			'name' => $this->name,
			'description' => $this->description,
			'nrOfTexts' => $this->nrOfTexts,
		];
	}
}
