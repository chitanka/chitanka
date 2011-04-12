<?php

namespace Chitanka\LibBundle\Entity;

/**
* @orm:Entity(repositoryClass="Chitanka\LibBundle\Entity\CategoryRepository")
* @orm:Table(name="category")
*/
class Category
{
	/**
	* @var integer $id
	* @orm:Id @orm:Column(type="integer") @orm:GeneratedValue(strategy="AUTO")
	*/
	private $id;

	/**
	* @var string $slug
	* @orm:Column(type="string", length=50, unique=true)
	* @assert:NotBlank()
	*/
	private $slug = '';

	/**
	* @var string $name
	* @orm:Column(type="string", length=80, unique=true)
	* @assert:NotBlank()
	*/
	private $name = '';

	/**
	* @var integer $parent
	* @orm:ManyToOne(targetEntity="Category", inversedBy="children")
	*/
	private $parent;

	/**
	* The children of this category
	* @var array
	* @orm:OneToMany(targetEntity="Category", mappedBy="parent")
	*/
	private $children;

	/**
	* @var array
	* @orm:OneToMany(targetEntity="Book", mappedBy="category")
	*/
	private $books;

	/**
	* Number of books in this category
	* @var integer
	* @orm:Column(type="integer")
	*/
	private $nr_of_books = 0;


	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = $slug; }
	public function getSlug() { return $this->slug; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setParent($parent) { $this->parent = $parent; }
	public function getParent() { return $this->parent; }

	public function setChildren($children) { $this->children = $children; }
	public function getChildren() { return $this->children; }

	public function setBooks($books) { $this->books = $books; }
	public function getBooks() { return $this->books; }

	public function getNrOfBooks() { return $this->nr_of_books; }

	public function __toString()
	{
		return $this->name;
	}

	/**
	* Get all ancestors
	*
	* @return array
	*/
	public function getAncestors()
	{
		$ancestors = array();
		$category = $this;
		while (null !== ($parent = $category->getParent())) {
			$ancestors[] = $parent;
			$category = $parent;
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
		foreach ($this->getChildren() as $category) {
			$ids[] = $category->getId();
			$ids = array_merge($ids, $category->getDescendantIds());
		}

		return $ids;
	}
}
