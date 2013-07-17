<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\BookRevisionRepository")
* @ORM\Table(name="book_revision",
*	indexes={
*		@ORM\Index(name="book_idx", columns={"book_id"}),
*		@ORM\Index(name="date_idx", columns={"date"})}
* )
*/
class BookRevision extends Entity
{
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="Chitanka\LibBundle\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var integer
	 * @ORM\ManyToOne(targetEntity="Book", inversedBy="revisions")
	 */
	private $book;

	/**
	 * @var string $comment
	 * @ORM\Column(type="string", length=255)
	 */
	private $comment;

	/**
	 * @var datetime $date
	 * @ORM\Column(type="datetime")
	 */
	private $date;

	/**
	 * @var boolean
	 * @ORM\Column(type="boolean")
	 */
	private $first;


	public function getId() { return $this->id; }

	public function setBook($book) { $this->book = $book; }
	public function getBook() { return $this->book; }

	public function setComment($comment) { $this->comment = $comment; }
	public function getComment() { return $this->comment; }

	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }

	public function setFirst($first) { $this->first = $first; }
	public function getFirst() { return $this->first; }

}
