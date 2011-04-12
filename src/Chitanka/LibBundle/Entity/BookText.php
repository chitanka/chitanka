<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity
* @orm:Table(name="book_text",
*	uniqueConstraints={@orm:UniqueConstraint(name="book_text_uniq", columns={"book_id", "text_id"})},
*	indexes={
*		@orm:Index(name="text_idx", columns={"text_id"})}
* )
*/
class BookText
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var integer $book
	* @orm:ManyToOne(targetEntity="Book", cascade={"ALL"})
	*/
	private $book;

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
	* @var boolean $share_info
	* @orm:Column(type="boolean")
	*/
	private $share_info;

	public function getId() { return $this->id; }

	public function setBook($book) { $this->book = $book; }
	public function getBook() { return $this->book; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setPos($pos) { $this->pos = $pos; }
	public function getPos() { return $this->pos; }

	public function setShareInfo($shareInfo) { $this->share_info = $shareInfo; }
	public function getShareInfo() { return $this->share_info; }

}
