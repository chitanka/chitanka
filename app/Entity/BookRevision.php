<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass="App\Entity\BookRevisionRepository")
* @ORM\Table(name="book_revision",
*	indexes={
*		@ORM\Index(name="book_idx", columns={"book_id"}),
*		@ORM\Index(name="date_idx", columns={"date"})}
* )
*/
class BookRevision extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var Book
	 * @ORM\ManyToOne(targetEntity="Book", inversedBy="revisions")
	 */
	private $book;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255)
	 */
	private $comment;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime")
	 */
	private $date;

	/**
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private $first;

	public function getId() { return $this->id; }

	public function setBook($book) { $this->book = $book; }
	public function getBook() { return $this->book; }

	public function setComment($comment) { $this->comment = $comment; }
	public function getComment() { return $this->comment; }

	/**
	 * @param \DateTime $date
	 */
	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }

	/**
	 * @param bool $first
	 */
	public function setFirst($first) { $this->first = $first; }
	public function getFirst() { return $this->first; }

}
