<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity
* @orm:Table(name="text_translator",
*	uniqueConstraints={@orm:UniqueConstraint(name="person_text_uniq", columns={"person_id", "text_id"})},
*	indexes={
*		@orm:Index(name="text_idx", columns={"text_id"})}
* )
*/
class TextTranslator
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var integer $person
	* @orm:ManyToOne(targetEntity="Person", cascade={"ALL"})
	*/
	private $person;

	/**
	* @var integer $text
	* @orm:ManyToOne(targetEntity="Text", cascade={"ALL"})
	*/
	private $text;

	/**
	* @var integer $pos
	* @orm:Column(type="smallint")
	*/
	private $pos;

	/**
	* @var integer $year
	* @orm:Column(type="smallint")
	*/
	private $year;

	public function getId() { return $this->id; }

	public function setPerson($person) { $this->person = $person; }
	public function getPerson() { return $this->person; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	/**
	* Set pos
	*
	* @param integer $pos
	*/
	public function setPos($pos)
	{
		$this->pos = $pos;
	}

	/**
	* Get pos
	*
	* @return integer $pos
	*/
	public function getPos()
	{
		return $this->pos;
	}

	/**
	* Set year
	*
	* @param integer $year
	*/
	public function setYear($year)
	{
		$this->year = $year;
	}

	/**
	* Get year
	*
	* @return integer $year
	*/
	public function getYear()
	{
		return $this->year;
	}

}
