<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Persistence\BookIsbnRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Table(name="book_isbn",
 *     indexes={
 *         @ORM\Index(name="code_idx", columns={"code"})
 *     }
 * )
 */
class BookIsbn extends Entity implements \JsonSerializable {

	public static function normalizeIsbn($isbn) {
		return preg_replace('/[^\dX]/', '', $isbn);
	}

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
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $book;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=20)
	 */
	private $code;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=50, nullable=true)
	 */
	private $addition;

	public function getId() { return $this->id; }

	public function setBook(Book $book) { $this->book = $book; }
	public function getBook() { return $this->book; }

	public function setCode($code) { $this->code = self::normalizeIsbn($code); }
	public function getCode() { return $this->code; }

	public function setAddition($addition) { $this->addition = $addition; }
	public function getAddition() { return $this->addition; }

	public function __toString() {
		return $this->getCode();
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'code' => $this->getCode(),
			'addition' => $this->getAddition(),
		];
	}
}
