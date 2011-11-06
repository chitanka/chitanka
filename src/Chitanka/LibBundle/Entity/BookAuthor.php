<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="book_author",
*	uniqueConstraints={@ORM\UniqueConstraint(name="person_book_uniq", columns={"person_id", "book_id"})}
* )
*/
class BookAuthor
{
	/**
	* @var integer $id
	* @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
	*/
	private $id;

	/**
	* @var integer $person
	* @ORM\ManyToOne(targetEntity="Person", inversedBy="bookAuthors")
	*/
	private $person;

	/**
	* @var integer $book
	* @ORM\ManyToOne(targetEntity="Book", inversedBy="bookAuthors")
	*/
	private $book;

	public function getId() { return $this->id; }

	public function setPerson($person) { $this->person = $person; }
	public function getPerson() { return $this->person; }

	public function setBook($book) { $this->book = $book; }
	public function getBook() { return $this->book; }

}
