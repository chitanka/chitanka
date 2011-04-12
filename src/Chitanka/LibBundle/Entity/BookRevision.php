<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\BookRevisionRepository")
* @orm:Table(name="book_revision",
*	indexes={
*		@orm:Index(name="book_idx", columns={"book_id"})}
* )
*/
class BookRevision
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue
	*/
	private $id;

	/**
	* @var integer
	* @orm:ManyToOne(targetEntity="Book", cascade={"ALL"})
	*/
	private $book;

	/**
	* @var string $comment
	* @orm:Column(type="string", length=255)
	*/
	private $comment;

	/**
	* @var datetime $date
	* @orm:Column(type="datetime")
	*/
	private $date;


	public function getId() { return $this->id; }

	public function setBook($book) { $this->book = $book; }
	public function getBook() { return $this->book; }

	public function setComment($comment) { $this->comment = $comment; }
	public function getComment() { return $this->comment; }

	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }

}
