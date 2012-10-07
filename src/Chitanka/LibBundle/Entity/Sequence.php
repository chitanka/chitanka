<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Chitanka\LibBundle\Util\String;

/**
 * @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\SequenceRepository")
 * @ORM\Table(name="sequence",
 *	indexes={
 *		@ORM\Index(name="name_idx", columns={"name"}),
 *		@ORM\Index(name="publisher_idx", columns={"publisher"})}
 * )
 * @UniqueEntity(fields="slug", message="This slug is already in use.")
 */
class Sequence extends Entity
{
	/**
	* @var integer $id
	* @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
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
	private $is_seqnr_visible = true;

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

	public function setIsSeqnrVisible($is_seqnr_visible) { $this->is_seqnr_visible = $is_seqnr_visible; }
	public function getIsSeqnrVisible() { return $this->is_seqnr_visible; }
	public function isSeqnrVisible() { return $this->is_seqnr_visible; }

	public function getBooks() { return $this->books; }

	public function __toString()
	{
		return $this->name;
	}
}
