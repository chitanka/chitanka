<?php namespace App\Entity;

use App\Util\Stringy;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Entity\PersonRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Table(name="person",
 *	indexes={
 *		@ORM\Index(name="name_idx", columns={"name"}),
 *		@ORM\Index(name="last_name_idx", columns={"last_name"}),
 *		@ORM\Index(name="orig_name_idx", columns={"orig_name"}),
 *		@ORM\Index(name="real_name_idx", columns={"real_name"}),
 *		@ORM\Index(name="oreal_name_idx", columns={"oreal_name"}),
 *		@ORM\Index(name="country_idx", columns={"country"}),
 *		@ORM\Index(name="is_author_idx", columns={"is_author"}),
 *		@ORM\Index(name="is_translator_idx", columns={"is_translator"})}
 * )
 * @UniqueEntity(fields="slug", message="This slug is already in use.")
 */
class Person extends Entity implements \JsonSerializable {
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
	private $slug;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100)
	 */
	private $name = '';

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $origName;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $realName;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $orealName;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=50, nullable=true)
	 */
	private $lastName;

	/**
	 * @var Country
	 * @ORM\ManyToOne(targetEntity="Country")
	 * @ORM\JoinColumn(name="country", referencedColumnName="code", nullable=false)
	 */
	private $country;

	/** @ORM\Column(type="boolean") */
	private $isAuthor = true;

	/** @ORM\Column(type="boolean") */
	private $isTranslator = false;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=160, nullable=true)
	 */
	private $info;

	/**
	 * @var Person
	 * @ORM\ManyToOne(targetEntity="Person")
	 */
	private $person;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=1, nullable=true)
	 */
	private $type;

//	/**
//	 * @var TextAuthor[]
//	 * @ORM\OneToMany(targetEntity="TextAuthor", mappedBy="person")
//	 */
//	private $textAuthors;
//
//	/**
//	 * @var TextTranslator[]
//	 * @ORM\OneToMany(targetEntity="TextTranslator", mappedBy="person")
//	 */
//	private $textTranslators;

	/**
	 * @var Book[]
	 * @ORM\ManyToMany(targetEntity="Book")
	 */
	private $books;

	/**
	 * @var Series[]
	 * @ORM\ManyToMany(targetEntity="Series", mappedBy="authors")
	 * @ORM\JoinTable(name="series_author")
	 */
	private $series;

	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = Stringy::slugify($slug); }
	public function getSlug() { return $this->slug; }

	public function setName($name) {
		$this->name = $name;
		$this->lastName = self::getLastNameFromName($name);
		if (empty($this->slug)) {
			$this->setSlug($name);
		}
	}
	public function getName() { return $this->name; }

	public function getLastNameFromName($name) {
		preg_match('/([^,]+) ([^,]+)(, .+)?/', $name, $m);
		return isset($m[2]) ? $m[2] : $name;
	}

	public function setOrigName($origName) {
		$this->origName = $origName;
		if (empty($this->slug) && preg_match('/[a-z]/', $origName)) {
			$this->setSlug($origName);
		}
	}
	public function getOrigName() { return $this->origName; }

	public function setRealName($realName) { $this->realName = $realName; }
	public function getRealName() { return $this->realName; }

	public function setOrealName($orealName) { $this->orealName = $orealName; }
	public function getOrealName() { return $this->orealName; }

	public function getLastName() { return $this->lastName; }

	public function setCountry($country) { $this->country = $country; }
	public function getCountry() { return $this->country; }

	public function getIsAuthor() { return $this->isAuthor; }
	public function getIsTranslator() { return $this->isTranslator; }
	public function setIsAuthor($isAuthor) { $this->isAuthor = $isAuthor; }
	public function setIsTranslator($isTranslator) { $this->isTranslator = $isTranslator; }

	public function isAuthor($isAuthor = null) {
		if ($isAuthor !== null) {
			$this->isAuthor = $isAuthor;
		}
		return $this->isAuthor;
	}

	public function isTranslator($isTranslator = null) {
		if ($isTranslator !== null) {
			$this->isTranslator = $isTranslator;
		}
		return $this->isTranslator;
	}

	public function getRole() {
		$roles = [];
		if ($this->isAuthor) $roles[] = 'author';
		if ($this->isTranslator) $roles[] = 'translator';

		return implode(',', $roles);
	}

	public function setInfo($info) { $this->info = $info; }
	public function getInfo() { return $this->info; }

	public function setPerson($person) { $this->person = $person; }
	public function getPerson() { return $this->person; }

	public function setType($type) { $this->type = $type; }
	public function getType() { return $this->type; }

	public function getBooks() { return $this->books; }
	public function getSeries() { return $this->series; }

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
			'id' => $this->getId(),
			'slug' => $this->getSlug(),
			'name' => $this->getName(),
			'lastName' => $this->getLastName(),
			'origName' => $this->getOrigName(),
			'realName' => $this->getRealName(),
			'origRealName' => $this->getOrealName(),
			'isAuthor' => $this->isAuthor(),
			'isTranslator' => $this->isTranslator(),
			'info' => $this->getInfo(),
			'type' => $this->getType(),
		];
	}
}
