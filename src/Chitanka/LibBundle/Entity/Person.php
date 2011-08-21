<?php

namespace Chitanka\LibBundle\Entity;

use Chitanka\LibBundle\Util\String;

#use Symfony\Component\Validator\Constraints;
#use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\PersonRepository")
* @orm:Table(name="person",
*	indexes={
*		@orm:Index(name="name_idx", columns={"name"}),
*		@orm:Index(name="last_name_idx", columns={"last_name"}),
*		@orm:Index(name="orig_name_idx", columns={"orig_name"}),
*		@orm:Index(name="country_idx", columns={"country"}),
*		@orm:Index(name="is_author_idx", columns={"is_author"}),
*		@orm:Index(name="is_translator_idx", columns={"is_translator"})}
* )
*/
class Person
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var string $slug
	* @orm:Column(type="string", length=50, unique=true)
	*/
	private $slug;

	/**
	* @var string $name
	* @orm:Column(type="string", length=100)
	*/
	private $name;

	/**
	* @var string $orig_name
	* @orm:Column(type="string", length=100, nullable=true)
	*/
	private $orig_name;

	/**
	* @var string $real_name
	* @orm:Column(type="string", length=100, nullable=true)
	*/
	private $real_name;

	/**
	* @var string $oreal_name
	* @orm:Column(type="string", length=100, nullable=true)
	*/
	private $oreal_name;

	/**
	* @var string $last_name
	* @orm:Column(type="string", length=50, nullable=true)
	*/
	private $last_name;

	/**
	* @var string $country
	* @orm:Column(type="string", length=10)
	*/
	private $country;

	/** @orm:Column(type="boolean") */
	private $is_author = true;

	/** @orm:Column(type="boolean") */
	private $is_translator = false;

	/**
	* @var string $info
	* @orm:Column(type="string", length=160, nullable=true)
	*/
	private $info;

	/**
	* @var integer $person
	* @orm:ManyToOne(targetEntity="Person", cascade={"ALL"})
	*/
	private $person;

	/**
	* @var string $type
	* @orm:Column(type="string", length=1, nullable=true)
	*/
	private $type;

	/**
	* @orm:ManyToMany(targetEntity="Text", mappedBy="authors")
	*/
	private $textsAsAuthor;

	/**
	* @orm:ManyToMany(targetEntity="Text", mappedBy="translators")
	*/
	private $textsAsTranslator;

	/**
	* @orm:ManyToMany(targetEntity="Book", mappedBy="authors")
	*/
	private $books;

	/**
	* @orm:ManyToMany(targetEntity="Series", mappedBy="authors")
	* @orm:JoinTable(name="series_author")
	*/
	private $series;


	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = $slug; }
	public function getSlug() { return $this->slug; }

	public function setName($name)
	{
		$this->name = $name;
		$this->last_name = self::getLastNameFromName($name);
		if (empty($this->slug)) {
			$this->slug = String::slugify($name);
		}
	}
	public function getName() { return $this->name; }

	public function getLastNameFromName($name)
	{
		preg_match('/([^,]+) ([^,]+)(, .+)?/', $name, $m);
		return isset($m[2]) ? $m[2] : $name;
	}

	public function setOrigName($origName)
	{
		$this->orig_name = $origName;
		if (empty($this->slug) && preg_match('/[a-z]/', $origName)) {
			$this->slug = String::slugify($origName);
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

	public function isAuthor($isAuthor = null)
	{
		if ($isAuthor !== null) {
			$this->is_author = $isAuthor;
		}
		return $this->is_author;
	}

	public function isTranslator($isTranslator = null)
	{
		if ($isTranslator !== null) {
			$this->is_translator = $isTranslator;
		}
		return $this->is_translator;
	}

	public function setInfo($info) { $this->info = $info; }
	public function getInfo() { return $this->info; }

	public function setPerson($person) { $this->person = $person; }
	public function getPerson() { return $this->person; }

	public function setType($type) { $this->type = $type; }
	public function getType() { return $this->type; }

	public function getBooks() { return $this->books; }

	public function __toString()
	{
		return $this->name;
	}
}
