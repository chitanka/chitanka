<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="text_translator",
*	uniqueConstraints={@ORM\UniqueConstraint(name="person_text_uniq", columns={"person_id", "text_id"})},
*	indexes={
*		@ORM\Index(name="text_idx", columns={"text_id"})}
* )
*/
class TextTranslator extends Entity
{
	/**
	* @var integer $id
	* @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
	*/
	private $id;

	/**
	* @var integer $person
	* @ORM\ManyToOne(targetEntity="Person", inversedBy="textTranslators")
	*/
	private $person;

	/**
	* @var integer $text
	* @ORM\ManyToOne(targetEntity="Text", inversedBy="textTranslators")
	*/
	private $text;

	/**
	* @var integer $pos
	* @ORM\Column(type="smallint", nullable=true)
	*/
	private $pos;

	/**
	* @var integer $year
	* @ORM\Column(type="smallint", nullable=true)
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
