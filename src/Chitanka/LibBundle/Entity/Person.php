<?php

namespace Chitanka\LibBundle\Entity;

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
*		@orm:Index(name="role_idx", columns={"role"})}
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

	/**
	* @var integer $role
	* @orm:Column(type="smallint")
	* Values: 1 - author, 2 - translator, 3 - both
	*/
	private $role = 1;

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

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setOrigName($origName) { $this->orig_name = $origName; }
	public function getOrigName() { return $this->orig_name; }
	public function orig_name() { return $this->orig_name; }

	public function setRealName($realName) { $this->real_name = $realName; }
	public function getRealName() { return $this->real_name; }

	public function setOrealName($orealName) { $this->oreal_name = $orealName; }
	public function getOrealName() { return $this->oreal_name; }

	public function setLastName($lastName) { $this->last_name = $lastName; }
	public function getLastName() { return $this->last_name; }

	public function setCountry($country) { $this->country = $country; }
	public function getCountry() { return $this->country; }

	public function setRole($role) { $this->role = $role; }
	public function getRole() { return $this->role; }

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
