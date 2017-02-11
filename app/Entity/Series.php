<?php namespace App\Entity;

use App\Util\Stringy;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Entity\SeriesRepository")
 * @ORM\Cache(usage="READ_ONLY")
 * @ORM\Table(name="series",
 *	indexes={
 *		@ORM\Index(name="name_idx", columns={"name"}),
 *		@ORM\Index(name="orig_name_idx", columns={"orig_name"})}
 * )
 * @UniqueEntity(fields="slug", message="This slug is already in use.")
 */
class Series extends Entity implements \JsonSerializable {
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
	private $slug;

	/**
	 * @var string $name
	 * @ORM\Column(type="string", length=100)
	 */
	private $name = '';

	/**
	 * @var string $origName
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $origName;

	/** FIXME doctrine:schema:create does not allow this relation
	 * @var array
	 * @ORM\ManyToMany(targetEntity="Person", inversedBy="series")
	 * @ORM\JoinTable(name="series_author",
	 *	joinColumns={@ORM\JoinColumn(name="series_id", referencedColumnName="id")},
	 *	inverseJoinColumns={@ORM\JoinColumn(name="person_id", referencedColumnName="id")})
	 */
	private $authors;

	/**
	 * @var array
	 * @ORM\OneToMany(targetEntity="SeriesAuthor", mappedBy="series", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private $seriesAuthors;

	/**
	 * @var array
	 * @ORM\OneToMany(targetEntity="Text", mappedBy="series")
	 * @ORM\OrderBy({"sernr" = "ASC"})
	 */
	private $texts;

	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = Stringy::slugify($slug); }
	public function getSlug() { return $this->slug; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setOrigName($origName) { $this->origName = $origName; }
	public function getOrigName() { return $this->origName; }

	public function getAuthors() { return $this->authors; }

	public function addSeriesAuthors(SeriesAuthor $seriesAuthor) { $this->seriesAuthors[] = $seriesAuthor; }
	public function setSeriesAuthors($seriesAuthors) { $this->seriesAuthors = $seriesAuthors; }
	public function getSeriesAuthors() { return $this->seriesAuthors; }

	public function getTexts() { return $this->texts; }

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
			'origName' => $this->getOrigName(),
			'authors' => $this->getAuthors(),
		];
	}
}
