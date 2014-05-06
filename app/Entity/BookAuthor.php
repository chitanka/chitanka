<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="book_author",
*	uniqueConstraints={@ORM\UniqueConstraint(name="person_book_uniq", columns={"person_id", "book_id"})}
* )
*/
class BookAuthor extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var Person
	 * @ORM\ManyToOne(targetEntity="Person")
	 */
	private $person;

	/**
	 * @var Book
	 * @ORM\ManyToOne(targetEntity="Book", inversedBy="bookAuthors")
	 */
	private $book;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $pos;

	public function getId() { return $this->id; }

	public function setPerson($person) { $this->person = $person; }
	public function getPerson() { return $this->person; }

	public function setBook($book) { $this->book = $book; }
	public function getBook() { return $this->book; }

	public function setPos($pos) { $this->pos = $pos; }
	public function getPos() { return $this->pos; }
}
