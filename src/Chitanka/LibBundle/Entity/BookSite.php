<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity
* @orm:Table(name="book_site")
*/
class BookSite
{
	/**
	* @var integer
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var string
	* @orm:Column(type="string", length=50, unique=true)
	*/
	private $name;

	/**
	* @var string
	* @orm:Column(type="string", length=100)
	*/
	private $url;

	public function getId() { return $this->id; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setUrl($url) { $this->url = $url; }
	public function getUrl() { return $this->url; }
}
