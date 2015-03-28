<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Util\String;

/**
 * @ORM\Entity(repositoryClass="App\Entity\CategoryRepository")
 * @ORM\Table(name="category")
 * @UniqueEntity(fields="slug", message="This slug is already in use.")
 * @UniqueEntity(fields="name")
 */
class Category extends Entity implements \JsonSerializable {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=50, unique=true)
	 * @Assert\NotBlank
	 */
	private $slug = '';

	/**
	 * @var string
	 * @ORM\Column(type="string", length=80, unique=true)
	 * @Assert\NotBlank
	 */
	private $name = '';

	/**
	 * @var Category
	 * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
	 */
	private $parent;

	/**
	 * The children of this category
	 * @var Category[]
	 * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
	 * @ORM\OrderBy({"name" = "ASC"})
	 */
	private $children;

	/**
	 * @var Book[]
	 * @ORM\OneToMany(targetEntity="Book", mappedBy="category")
	 * @ORM\OrderBy({"title" = "ASC"})
	 */
	private $books;

	/**
	 * Number of books in this category
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $nrOfBooks = 0;

	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = String::slugify($slug); }
	public function getSlug() { return $this->slug; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setParent($parent) { $this->parent = $parent; }
	public function getParent() { return $this->parent; }

	public function setChildren($children) { $this->children = $children; }
	public function getChildren() { return $this->children; }

	public function setBooks($books) { $this->books = $books; }
	public function getBooks() { return $this->books; }

	public function setNrOfBooks($nrOfBooks) { $this->nrOfBooks = $nrOfBooks; }
	public function getNrOfBooks() { return $this->nrOfBooks; }
	public function incNrOfBooks($value = 1) {
		$this->nrOfBooks += $value;
	}

	public function __toString() {
		return $this->name;
	}

	/**
	 * Get all ancestors
	 *
	 * @return Category[]
	 */
	public function getAncestors() {
		$ancestors = [];
		$category = $this;
		while (null !== ($parent = $category->getParent())) {
			$ancestors[] = $parent;
			$category = $parent;
		}

		return $ancestors;
	}

	public function getDescendantIdsAndSelf() {
		return array_merge([$this->getId()], $this->getDescendantIds());
	}

	/**
	 * Get all descendants
	 *
	 * @return array Array of IDs
	 */
	public function getDescendantIds() {
		$ids = [];
		foreach ($this->getChildren() as $category) {
			$ids[] = $category->getId();
			$ids = array_merge($ids, $category->getDescendantIds());
		}

		return $ids;
	}

	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'slug' => $this->slug,
			'name' => $this->name,
			'nrOfBooks' => $this->nrOfBooks,
		];
	}

}
