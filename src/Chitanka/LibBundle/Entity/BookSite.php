<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="book_site")
*/
class BookSite extends Entity
{
	/**
	* @var integer
	* @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
	*/
	private $id;

	/**
	* @var string
	* @ORM\Column(type="string", length=50, unique=true)
	*/
	private $name;

	/**
	* @var string
	* @ORM\Column(type="string", length=100)
	*/
	private $url;

	public function __toString()
	{
		return $this->name;
	}

	public function getId() { return $this->id; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setUrl($url) { $this->url = $url; }
	public function getUrl() { return $this->url; }
}
