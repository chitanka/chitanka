<?php namespace App\Entity;

use App\Util\Stringy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @ORM\Entity(repositoryClass="App\Entity\ForeignBookRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Table(name="foreign_book")
 */
class ForeignBook extends Entity implements \JsonSerializable {

	const FORMAT_PAPER = 'paper';
	const FORMAT_EPUB = 'epub';
	const FORMAT_FB2 = 'fb2';
	const FORMAT_MOBI = 'mobi';
	const FORMAT_PDF = 'pdf';

	const FORMATS = [
		self::FORMAT_PAPER,
		self::FORMAT_EPUB,
		self::FORMAT_FB2,
		self::FORMAT_MOBI,
		self::FORMAT_PDF,
	];

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100)
	 */
	private $author;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100)
	 */
	private $title;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $sequence;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $sequenceNo;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255)
	 */
	private $externalUrl;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255)
	 */
	private $cover;
	/**
	 * @var UploadedFile
	 */
	private $coverFile;

	/**
	 * @var string
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $annotation;

	/**
	 * @var string
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $excerpt;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="date", nullable=true)
	 */
	private $publishedAt;

	/**
	 * @var Publisher
	 * @ORM\ManyToOne(targetEntity="Publisher", inversedBy="foreignBooks")
	 */
	private $publisher;

	/**
	 * @var Category
	 * @ORM\ManyToOne(targetEntity="Category")
	 */
	private $category;

	/**
	 * @var ArrayCollection|Label[]
	 * @ORM\ManyToMany(targetEntity="Label")
	 * @ORM\OrderBy({"name" = "ASC"})
	 */
	private $labels;

	/**
	 * @var array
	 * @ORM\Column(type="simple_array")
	 */
	private $formats;

	/**
	 * Used by publishers
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private $isActive = true;

	/**
	 * Used by moderators. Overwrites isActive.
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private $isEnabled = true;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="date", nullable=true)
	 */
	private $validSince;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="date", nullable=true)
	 */
	private $validUntil;

	public function __construct() {
		$this->labels = new ArrayCollection();
		$this->formats = [self::FORMAT_PAPER];
	}

	public function getId() { return $this->id; }

	public function setAuthor($author) { $this->author = $author; }
	public function getAuthor() { return $this->author; }

	public function setTitle($title) { $this->title = $title; }
	public function getTitle() { return $this->title; }

	/**
	 * @return string
	 */
	public function getSequence() {
		return $this->sequence;
	}

	/**
	 * @param string $sequence
	 */
	public function setSequence($sequence) {
		$this->sequence = $sequence;
	}

	/**
	 * @return int
	 */
	public function getSequenceNo() {
		return $this->sequenceNo;
	}

	/**
	 * @param int $sequenceNo
	 */
	public function setSequenceNo($sequenceNo) {
		$this->sequenceNo = $sequenceNo;
	}

	public function setExternalUrl($externalUrl) { $this->externalUrl = $externalUrl; }
	public function getExternalUrl() { return $this->externalUrl; }

	public function setCover($cover) { $this->cover = $cover; }
	public function getCover() { return $this->cover; }

	/**
	 * @param UploadedFile $file
	 */
	public function setCoverFile(UploadedFile $file = null) {
		$this->coverFile = $file;
	}

	/**
	 * @return UploadedFile
	 */
	public function getCoverFile() {
		return $this->coverFile;
	}

	public function setAnnotation($annotation) { $this->annotation = $annotation; }
	public function getAnnotation() { return $this->annotation; }

	public function setExcerpt($excerpt) { $this->excerpt = $excerpt; }
	public function getExcerpt() { return $this->excerpt; }

	/**
	 * @return \DateTime
	 */
	public function getPublishedAt() {
		return $this->publishedAt;
	}

	/**
	 * @param \DateTime $publishedAt
	 */
	public function setPublishedAt($publishedAt) {
		$this->publishedAt = $publishedAt;
	}

	/**
	 * @return Publisher
	 */
	public function getPublisher() {
		return $this->publisher;
	}

	/**
	 * @param Publisher $publisher
	 */
	public function setPublisher(Publisher $publisher = null) {
		$this->publisher = $publisher;
	}

	/**
	 * @return Category
	 */
	public function getCategory() {
		return $this->category;
	}

	/**
	 * @param Category $category
	 */
	public function setCategory($category) {
		$this->category = $category;
	}

	/**
	 * @return Label[]|ArrayCollection
	 */
	public function getLabels() {
		return $this->labels;
	}

	/**
	 * @param Label[]|ArrayCollection $labels
	 */
	public function setLabels($labels) {
		$this->labels = $labels;
	}

	/**
	 * @return array
	 */
	public function getFormats() {
		return $this->formats;
	}

	/**
	 * @param array $formats
	 */
	public function setFormats($formats) {
		$this->formats = $formats;
	}

	/**
	 * @return boolean
	 */
	public function isActive() {
		return $this->isActive;
	}

	/**
	 * @param boolean $isActive
	 */
	public function setIsActive($isActive) {
		$this->isActive = $isActive;
	}

	/**
	 * @return boolean
	 */
	public function isEnabled() {
		return $this->isEnabled;
	}

	/**
	 * @param boolean $isEnabled
	 */
	public function setIsEnabled($isEnabled) {
		$this->isEnabled = $isEnabled;
	}


	/**
	 * @return \DateTime
	 */
	public function getValidSince() {
		return $this->validSince;
	}

	/**
	 * @param \DateTime $validSince
	 */
	public function setValidSince($validSince) {
		$this->validSince = $validSince;
	}

	/**
	 * @return \DateTime
	 */
	public function getValidUntil() {
		return $this->validUntil;
	}

	/**
	 * @param \DateTime $validUntil
	 */
	public function setValidUntil($validUntil) {
		$this->validUntil = $validUntil;
	}

	public function getCoverPath() {
		return $this->cover === null ? null : $this->getUploadDir().'/'.$this->cover;
	}

	protected function getUploadRootDir($basepath = '') {
		// the absolute directory path where uploaded documents should be saved
		return $basepath.$this->getUploadDir();
	}

	protected function getUploadDir() {
		return 'content/foreign-book-cover/'.$this->getPublisher()->getId();
	}

	public function upload($basepath) {
		if ($this->coverFile === null) {
			return;
		}
		if ($basepath === null) {
			return;
		}
		$name = uniqid().'.'. $this->coverFile->getClientOriginalExtension();
		$this->coverFile->move($this->getUploadRootDir($basepath), $name);
		$this->setCover($name);
		$this->setCoverFile(null);
	}

	public function __toString() {
		return $this->title;
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'title' => $this->getTitle(),
			'author' => $this->getAuthor(),
			'externalUrl' => $this->getExternalUrl(),
			'cover' => $this->getCover(),
			'annotation' => $this->getAnnotation(),
			'excerpt' => $this->getExcerpt(),
			'publishedAt' => $this->getPublishedAt(),
			'publisher' => $this->getPublisher(),
			'category' => $this->getCategory(),
			'labels' => $this->getLabels(),
		];
	}
}
