<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="book_text")
 */
class BookText extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var Book
	 * @ORM\ManyToOne(targetEntity="Book", inversedBy="bookTexts")
	 */
	private $book;

	/**
	 * @var Text
	 * @ORM\ManyToOne(targetEntity="Text", inversedBy="bookTexts")
	 */
	private $text;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $pos;

	/**
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private $shareInfo = true;

	public function getId() { return $this->id; }

	/**
	 * @param Book $book
	 */
	public function setBook($book) { $this->book = $book; }
	public function getBook() { return $this->book; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setPos($pos) { $this->pos = $pos; }
	public function getPos() { return $this->pos; }

	/**
	 * @param bool $shareInfo
	 */
	public function setShareInfo($shareInfo) { $this->shareInfo = $shareInfo; }
	public function getShareInfo() { return $this->shareInfo; }

}
