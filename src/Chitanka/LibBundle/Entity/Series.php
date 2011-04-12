<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\SeriesRepository")
* @orm:Table(name="series",
*	indexes={
*		@orm:Index(name="name_idx", columns={"name"})}
* )
*/
class Series
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var string $slug
	* @orm:Column(type="string", length=50)
	*/
	private $slug;

	/**
	* @var string $name
	* @orm:Column(type="string", length=100)
	*/
	private $name;

	/**
	* @var string $orig_name
	* @orm:Column(type="string", length=100)
	*/
	private $orig_name;

	/**
	* @var string $type
	* @orm:Column(type="string", length=10)
	*/
	private $type;

	/**
	* @var array
	* @orm:ManyToMany(targetEntity="Person", inversedBy="series")
	* @orm:JoinTable(name="series_author",
	*	joinColumns={@orm:JoinColumn(name="series_id", referencedColumnName="id")},
	*	inverseJoinColumns={@orm:JoinColumn(name="person_id", referencedColumnName="id")})
	*/
	private $authors;

	/**
	* @var array
	* @orm:OneToMany(targetEntity="Text", mappedBy="series")
	*/
	private $texts;

	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = $slug; }
	public function getSlug() { return $this->slug; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setOrigName($origName) { $this->orig_name = $origName; }
	public function getOrigName() { return $this->orig_name; }

	public function setType($type) { $this->type = $type; }
	public function getType() { return $this->type; }

	public function getAuthors() { return $this->authors; }

	public function getTexts() { return $this->texts; }

	public function __toString()
	{
		return $this->name;
	}
}
