<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Persistence\LanguageRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Table(name="language")
 */
class Language implements \JsonSerializable {

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
	 * Number of texts in this language
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $nrOfTexts = 0;

	/**
	 * Number of texts for which the original is in this language
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $nrOfTranslatedTexts = 0;

	public function getId() {
		return $this->code;
	}
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

	public function setNrOfTexts(int $nrOfTexts) { $this->nrOfTexts = $nrOfTexts; }
	public function getNrOfTexts(): int { return $this->nrOfTexts; }
	public function incNrOfTexts(int $value = 1) {
		$this->nrOfTexts += $value;
	}

	public function setNrOfTranslatedTexts(int $nrOfTranslatedTexts) { $this->nrOfTranslatedTexts = $nrOfTranslatedTexts; }
	public function getNrOfTranslatedTexts(): int { return $this->nrOfTranslatedTexts; }
	public function incNrOfTranslatedTexts(int $value = 1) {
		$this->nrOfTranslatedTexts += $value;
	}

	public function __toString() {
		return $this->name;
	}

	public function is(string $codeOrName): bool {
		return $this->code === $codeOrName || $this->name === $codeOrName;
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
		];
	}
}
