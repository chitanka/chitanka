<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Chitanka\LibBundle\Util\String;

/**
* @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\LabelRepository")
* @ORM\Table(name="label")
*/
class Label
{
	/**
	* @var integer $id
	* @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO")
	*/
	private $id;

	/**
	* @var string $slug
	* @ORM\Column(type="string", length=80, unique=true)
	* @Assert\NotBlank
	*/
	private $slug = '';

	/**
	* @var string $name
	* @ORM\Column(type="string", length=80, unique=true)
	* @Assert\NotBlank
	*/
	private $name = '';

	/**
	* @var integer $parent
	* @ORM\ManyToOne(targetEntity="Label", inversedBy="children")
	*/
	private $parent;

	/**
	* Number of texts having this label
	* @var integer $nr_of_texts
	* @ORM\Column(type="integer")
	*/
	private $nr_of_texts = 0;

	/**
	* The children of this label
	* @var array
	* @ORM\OneToMany(targetEntity="Label", mappedBy="parent")
	*/
	private $children;

	/**
	* @var array
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
	public function incNrOfTexts($value = 1)
	{
		$this->nr_of_texts += $value;
	}

	public function setChildren($children) { $this->children = $children; }
	public function getChildren() { return $this->children; }

	public function setTexts($texts) { $this->texts = $texts; }
	public function getTexts() { return $this->texts; }

	public function __toString()
	{
		return $this->name;
	}

	/**
	* Add child label
	*/
	public function addChild($label)
	{
		$this->children[] = $label;
	}

	/**
	* Get all ancestors
	*
	* @return array
	*/
	public function getAncestors()
	{
		$ancestors = array();
		$label = $this;
		while (null !== ($parent = $label->getParent())) {
			$ancestors[] = $parent;
			$label = $parent;
		}

		return $ancestors;
	}

	public function getDescendantIdsAndSelf()
	{
		return array_merge(array($this->getId()), $this->getDescendantIds());
	}

	/**
	* Get all descendants
	*
	* @return array
	*/
	public function getDescendantIds()
	{
		$ids = array();
		foreach ($this->getChildren() as $label) {
			$ids[] = $label->getId();
			$ids = array_merge($ids, $label->getDescendantIds());
		}

		return $ids;
	}
}
