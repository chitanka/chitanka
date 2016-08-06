<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Entity\LanguageRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Table(name="language")
 */
class Language {

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

	public function __toString() {
		return $this->name;
	}
}
