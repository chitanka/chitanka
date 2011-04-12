<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\SequenceRepository")
* @orm:Table(name="sequence",
*	indexes={
*		@orm:Index(name="name_idx", columns={"name"})}
* )
*/
class Sequence
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
	private $slug = '';

	/**
	* @var string $name
	* @orm:Column(type="string", length=100)
	*/
	private $name = '';

	/**
	* @var string
	* @orm:Column(type="string", length=100)
	*/
	private $publisher = '';

	/**
	* @var array
	* @orm:OneToMany(targetEntity="Book", mappedBy="sequence")
	*/
	private $books;

	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = $slug; }
	public function getSlug() { return $this->slug; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setPublisher($publisher) { $this->publisher = $publisher; }
	public function getPublisher() { return $this->publisher; }

	public function getBooks() { return $this->books; }

	public function __toString()
	{
		return $this->name;
	}
}
