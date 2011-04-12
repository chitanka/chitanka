<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\WantedBookRepository")
* @orm:Table(name="wanted_book",
*	indexes={
*		@orm:Index(name="name_idx", columns={"name"})}
* )
*/
class WantedBook
{
	/**
	* @var integer
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var string
	* @orm:Column(type="string", length=60)
	*/
	private $name;

	/**
	* @var string
	* @orm:Column(type="text")
	*/
	private $description;

	public function getId() { return $this->id; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setDescription($description) { $this->description = $description; }
	public function getDescription() { return $this->description; }

}
