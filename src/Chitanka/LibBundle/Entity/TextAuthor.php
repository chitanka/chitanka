<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="text_author",
*	uniqueConstraints={@ORM\UniqueConstraint(name="person_text_uniq", columns={"person_id", "text_id"})},
*	indexes={
*		@ORM\Index(name="text_idx", columns={"text_id"})}
* )
*/
class TextAuthor
{
	/**
	* @var integer $id
	* @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
	*/
	private $id;

	/**
	* @var integer $person
	* @ORM\ManyToOne(targetEntity="Person", inversedBy="textAuthors")
	*/
	private $person;

	/**
	* @var integer $text
	* @ORM\ManyToOne(targetEntity="Text", inversedBy="textTranslators")
	*/
	private $text;

	/**
	* @var integer $pos
	* @ORM\Column(type="smallint")
	*/
	private $pos;

	/**
	* @var integer $year
	* @ORM\Column(type="smallint")
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
