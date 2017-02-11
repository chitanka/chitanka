<?php namespace App\Entity;

use App\Util\Stringy;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Entity\SequenceRepository")
 * @ORM\Cache(usage="READ_ONLY")
 * @ORM\Table(name="sequence",
 *	indexes={
 *		@ORM\Index(name="name_idx", columns={"name"}),
 *		@ORM\Index(name="publisher_idx", columns={"publisher"})}
 * )
 * @UniqueEntity(fields="slug", message="This slug is already in use.")
 */
class Sequence extends Entity implements \JsonSerializable {
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
	 * @var string
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $annotation;

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

	public function setSlug($slug) { $this->slug = Stringy::slugify($slug); }
	public function getSlug() { return $this->slug; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setPublisher($publisher) { $this->publisher = $publisher; }
	public function getPublisher() { return $this->publisher; }

	/**
	 * @return string
	 */
	public function getAnnotation() {
		return $this->annotation;
	}

	/**
	 * @param string $annotation
	 */
	public function setAnnotation($annotation) {
		$this->annotation = $annotation;
	}

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

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'slug' => $this->getSlug(),
			'name' => $this->getName(),
			'publisher' => $this->getPublisher(),
			'anntation' => $this->getAnnotation(),
			'isSeqnrVisible' => $this->isSeqnrVisible(),
			'nrOfBooks' => $this->getNrOfBooks(),
		];
	}
}
