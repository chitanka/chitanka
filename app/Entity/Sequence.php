<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Util\String;

/**
 * @ORM\Entity(repositoryClass="App\Entity\SequenceRepository")
 * @ORM\Table(name="sequence",
 *	indexes={
 *		@ORM\Index(name="name_idx", columns={"name"}),
 *		@ORM\Index(name="publisher_idx", columns={"publisher"})}
 * )
 * @UniqueEntity(fields="slug", message="This slug is already in use.")
 */
class Sequence extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var string $slug
	 * @ORM\Column(type="string", length=50, unique=true)
	 */
	private $slug = '';

	/**
	 * @var string $name
	 * @ORM\Column(type="string", length=100)
	 */
	private $name = '';

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $publisher = '';

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $isSeqnrVisible = true;

	/**
	 * Number of books in this sequence
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $nrOfBooks = 0;

	/**
	 * @var array
	 * @ORM\OneToMany(targetEntity="Book", mappedBy="sequence")
	 * @ORM\OrderBy({"seqnr" = "ASC"})
	 */
	private $books;

	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = String::slugify($slug); }
	public function getSlug() { return $this->slug; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setPublisher($publisher) { $this->publisher = $publisher; }
	public function getPublisher() { return $this->publisher; }

	public function setIsSeqnrVisible($isSeqnrVisible) { $this->isSeqnrVisible = $isSeqnrVisible; }
	public function isSeqnrVisible() { return $this->isSeqnrVisible; }
	// TODO needed by sonata admin
	public function getIsSeqnrVisible() { return $this->isSeqnrVisible; }

	public function setNrOfBooks($nrOfBooks) { $this->nrOfBooks = $nrOfBooks; }
	public function getNrOfBooks() { return $this->nrOfBooks; }
	public function incNrOfBooks($value = 1) {
		$this->nrOfBooks += $value;
	}

	public function getBooks() { return $this->books; }

	public function __toString() {
		return $this->name;
	}
}
