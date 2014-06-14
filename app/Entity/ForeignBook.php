<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass="App\Entity\ForeignBookRepository")
* @ORM\Table(name="foreign_book")
*/
class ForeignBook extends Entity {
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
	private $title = '';

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100)
	 */
	private $author;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255)
	 */
	private $url;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255)
	 */
	private $cover;

	/**
	 * @var string
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $description;

	/**
	 * @var bool
	 * @ORM\Column(name="is_free", type="boolean")
	 */
	private $isFree;

	public function getId() { return $this->id; }

	public function setTitle($title) { $this->title = $title; }
	public function getTitle() { return $this->title; }

	public function setAuthor($author) { $this->author = $author; }
	public function getAuthor() { return $this->author; }

	public function setUrl($url) { $this->url = $url; }
	public function getUrl() { return $this->url; }

	public function setCover($cover) { $this->cover = $cover; }
	public function getCover() { return $this->cover; }

	public function setDescription($description) { $this->description = $description; }
	public function getDescription() { return $this->description; }

	public function setIsFree($isFree) { $this->isFree = $isFree; }
	public function getIsFree() { return $this->isFree; }

	public function __toString() {
		return $this->title;
	}
}
