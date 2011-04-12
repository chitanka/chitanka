<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity
* @orm:Table(name="text_author",
*	uniqueConstraints={@orm:UniqueConstraint(name="person_text_uniq", columns={"person_id", "text_id"})},
*	indexes={
*		@orm:Index(name="text_idx", columns={"text_id"})}
* )
*/
class TextAuthor
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var integer $person
	* @orm:ManyToOne(targetEntity="Person", inversedBy="textAuthors", cascade={"ALL"})
	*/
	private $person;

	/**
	* @var integer $text
	* @orm:ManyToOne(targetEntity="Text", inversedBy="textTranslators", cascade={"ALL"})
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

	public function setPos($pos) { $this->pos = $pos; }
	public function getPos() { return $this->pos; }

	public function setYear($year) { $this->year = $year; }
	public function getYear() { return $this->year; }

	/**
	* Add Text
	*
	* @param Chitanka\LibBundle\Entity\Text $text
	*/
	public function addText(\Chitanka\LibBundle\Entity\Text $text)
	{
		$this->texts[] = $text;
	}

	/**
	* Get Text
	*
	* @return Doctrine\Common\Collections\Collection $text
	*/
	public function getTexts()
	{
		return $this->texts;
	}

	/**
	* Add Person
	*
	* @param Chitanka\LibBundle\Entity\Person $person
	*/
	public function addPerson(\Chitanka\LibBundle\Entity\Person $person)
	{
		$this->persons[] = $person;
	}

	/**
	* Get Person
	*
	* @return Doctrine\Common\Collections\Collection $person
	*/
	public function getPersons()
	{
		return $this->persons;
	}
}
