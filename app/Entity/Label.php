<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Util\String;

/**
 * @ORM\Entity(repositoryClass="App\Entity\LabelRepository")
 * @ORM\Table(name="label")
 * @UniqueEntity(fields="slug", message="This slug is already in use.")
 * @UniqueEntity(fields="name")
 */
class Label extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=80, unique=true)
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
	 * @var Label
	 * @ORM\ManyToOne(targetEntity="Label", inversedBy="children")
	 */
	private $parent;

	/**
	 * Number of texts having this label
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $nr_of_texts = 0;

	/**
	 * The children of this label
	 * @var Label[]
	 * @ORM\OneToMany(targetEntity="Label", mappedBy="parent")
	 */
	private $children;

	/**
	 * @var Text[]
	 * @ORM\ManyToMany(targetEntity="Text", mappedBy="labels")
	 * @ORM\OrderBy({"title" = "ASC"})
	 */
	private $texts;

	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = String::slugify($slug); }
	public function getSlug() { return $this->slug; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setParent($parent) { $this->parent = $parent; }
	public function getParent() { return $this->parent; }

	public function setNrOfTexts($nr_of_texts) { $this->nr_of_texts = $nr_of_texts; }
	public function getNrOfTexts() { return $this->nr_of_texts; }
	public function incNrOfTexts($value = 1) {
		$this->nr_of_texts += $value;
	}

	public function setChildren($children) { $this->children = $children; }
	public function getChildren() { return $this->children; }

	public function setTexts($texts) { $this->texts = $texts; }
	public function getTexts() { return $this->texts; }

	public function __toString() {
		return $this->name;
	}

	/**
	 * Add child label
	 */
	public function addChild($label) {
		$this->children[] = $label;
	}

	/**
	 * Get all ancestors
	 *
	 * @return Label[]
	 */
	public function getAncestors() {
		$ancestors = array();
		$label = $this;
		while (null !== ($parent = $label->getParent())) {
			$ancestors[] = $parent;
			$label = $parent;
		}

		return $ancestors;
	}

	/**
	 * @return array Array of IDs
	 */
	public function getDescendantIdsAndSelf() {
		return array_merge(array($this->getId()), $this->getDescendantIds());
	}

	/**
	 * Get all descendants
	 *
	 * @return array Array of IDs
	 */
	public function getDescendantIds() {
		$ids = array();
		foreach ($this->getChildren() as $label) {
			$ids[] = $label->getId();
			$ids = array_merge($ids, $label->getDescendantIds());
		}

		return $ids;
	}
}
