<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="book_text",
*	uniqueConstraints={@ORM\UniqueConstraint(name="book_text_uniq", columns={"book_id", "text_id"})},
*	indexes={
*		@ORM\Index(name="text_idx", columns={"text_id"})}
* )
*/
class BookText extends Entity
{
	/**
	* @var integer $id
	* @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
	*/
	private $id;

	/**
	* @var integer $book
	* @ORM\ManyToOne(targetEntity="Book")
	*/
	private $book;

	/**
	* @var integer $text
	* @ORM\ManyToOne(targetEntity="Text", inversedBy="bookTexts")
	*/
	private $text;

	/**
	* @var integer $pos
	* @ORM\Column(type="smallint", nullable=true)
	*/
	private $pos;

	/**
	* @var boolean $share_info
	* @ORM\Column(type="boolean")
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
