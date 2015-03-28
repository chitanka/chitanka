<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Util\String;

/**
 * @ORM\Entity(repositoryClass="App\Entity\PersonRepository")
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
class Person extends Entity {
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
	private $orig_name;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $real_name;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $oreal_name;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=50, nullable=true)
	 */
	private $last_name;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=10)
	 */
	private $country;

	/** @ORM\Column(type="boolean") */
	private $is_author = true;

	/** @ORM\Column(type="boolean") */
	private $is_translator = false;

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

	public function setSlug($slug) { $this->slug = String::slugify($slug); }
	public function getSlug() { return $this->slug; }

	public function setName($name) {
		$this->name = $name;
		$this->last_name = self::getLastNameFromName($name);
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
		$this->orig_name = $origName;
		if (empty($this->slug) && preg_match('/[a-z]/', $origName)) {
			$this->setSlug($origName);
		}
	}
	public function getOrigName() { return $this->orig_name; }
	public function orig_name() { return $this->orig_name; }

	public function setRealName($realName) { $this->real_name = $realName; }
	public function getRealName() { return $this->real_name; }

	public function setOrealName($orealName) { $this->oreal_name = $orealName; }
	public function getOrealName() { return $this->oreal_name; }

	public function getLastName() { return $this->last_name; }

	public function setCountry($country) { $this->country = $country; }
	public function getCountry() { return $this->country; }

	public function getIsAuthor() { return $this->is_author; }
	public function getIsTranslator() { return $this->is_translator; }
	public function is_author() { return $this->is_author; }
	public function is_translator() { return $this->is_translator; }
	public function setIsAuthor($isAuthor) { $this->is_author = $isAuthor; }
	public function setIsTranslator($isTranslator) { $this->is_translator = $isTranslator; }

	public function isAuthor($isAuthor = null) {
		if ($isAuthor !== null) {
			$this->is_author = $isAuthor;
		}
		return $this->is_author;
	}

	public function isTranslator($isTranslator = null) {
		if ($isTranslator !== null) {
			$this->is_translator = $isTranslator;
		}
		return $this->is_translator;
	}

	public function getRole() {
		$roles = [];
		if ($this->is_author) $roles[] = 'author';
		if ($this->is_translator) $roles[] = 'translator';

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
}
