<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\WantedBookRepository")
* @ORM\Table(name="wanted_book",
*	indexes={
*		@ORM\Index(name="name_idx", columns={"name"})}
* )
*/
class WantedBook
{
	/**
	* @var integer
	* @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
	*/
	private $id;

	/**
	* @var string
	* @ORM\Column(type="string", length=60)
	*/
	private $name;

	/**
	* @var string
	* @ORM\Column(type="text")
	*/
	private $description;

	public function getId() { return $this->id; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setDescription($description) { $this->description = $description; }
	public function getDescription() { return $this->description; }

}
