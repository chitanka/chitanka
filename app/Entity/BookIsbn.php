<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Entity\BookIsbnRepository")
 * @ORM\Table(name="book_isbn",
 *     indexes={
 *         @ORM\Index(name="code_idx", columns={"code"})
 *     }
 * )
 */
class BookIsbn extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var Book
	 * @ORM\ManyToOne(targetEntity="Book", inversedBy="isbns")
	 */
	private $book;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=20)
	 */
	private $code;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=50)
	 */
	private $addition;

	public function getId() { return $this->id; }

	public function setBook($book) { $this->book = $book; }
	public function getBook() { return $this->book; }

	public function setCode($code) { $this->code = $code; }
	public function getCode() { return $this->code; }

	public function setAddition($addition) { $this->addition = $addition; }
	public function getAddition() { return $this->addition; }

	public function __toString() {
		return $this->getCode();
	}

}
