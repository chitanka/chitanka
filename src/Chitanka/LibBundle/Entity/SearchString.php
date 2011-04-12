<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\SearchStringRepository")
* @orm:Table(name="search_string")
*/
class SearchString
{
	/**
	* @var integer
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var string
	* @orm:Column(type="string", length=100, unique=true)
	*/
	private $name;

	/**
	* @var integer
	* @orm:Column(type="integer")
	*/
	private $count = 0;

	/**
	* @var \DateTime
	* @orm:Column(type="datetime")
	*/
	private $date;

	public function __construct($name)
	{
		$this->name = $name;
		$this->date = new \DateTime;
	}

	public function getId() { return $this->id; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setCount($count) { $this->count = $count; }
	public function getCount() { return $this->count; }
	public function incCount() {
		$this->count++;
		$this->date = new \DateTime;
	}

	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }
}
