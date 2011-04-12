<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\TextHeaderRepository")
* @orm:Table(name="text_header",
*	uniqueConstraints={@orm:UniqueConstraint(name="key_uniq", columns={"text_id", "nr", "level"})}
* )
*/
class TextHeader
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var integer $text
	* @orm:ManyToOne(targetEntity="Text", cascade={"ALL"})
	*/
	private $text;

	/**
	* @var integer $nr
	* @orm:Column(type="smallint")
	*/
	private $nr;

	/**
	* @var integer $level
	* @orm:Column(type="smallint")
	*/
	private $level;

	/**
	* @var string $name
	* @orm:Column(type="string", length=255)
	*/
	private $name;

	/**
	* @var integer $fpos
	* @orm:Column(type="integer")
	*/
	private $fpos;

	/**
	* @var integer $linecnt
	* @orm:Column(type="smallint")
	*/
	private $linecnt;


	public function getId() { return $this->id; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setNr($nr) { $this->nr = $nr; }
	public function getNr() { return $this->nr; }

	public function setLevel($level) { $this->level = $level; }
	public function getLevel() { return $this->level; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setFpos($fpos) { $this->fpos = $fpos; }
	public function getFpos() { return $this->fpos; }

	public function setLinecnt($linecnt) { $this->linecnt = $linecnt; }
	public function getLinecnt() { return $this->linecnt; }

}
