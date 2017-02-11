<?php namespace App\Entity;

use App\Util\Stringy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Entity\PublisherRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Table(name="publisher")
 */
class Publisher extends Entity implements \JsonSerializable {

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=50)
	 */
	private $slug;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100)
	 */
	private $name;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100)
	 */
	private $website;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100)
	 */
	private $email;

	/**
	 * @var string
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $extraInfo;

	/**
	 * @var User[]
	 * @ORM\OneToMany(targetEntity="User", mappedBy="publisher")
	 */
	private $users;

	/**
	 * @var ForeignBook[]
	 * @ORM\OneToMany(targetEntity="ForeignBook", mappedBy="publisher")
	 * @ORM\OrderBy({"publishedAt" = "DESC"})
	 */
	private $foreignBooks;

	public function __construct() {
		$this->users = new ArrayCollection();
		$this->foreignBooks = new ArrayCollection();
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getSlug() {
		return $this->slug;
	}

	/**
	 * @param string $slug
	 */
	public function setSlug($slug) {
		$this->slug = Stringy::slugify($slug);
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
		if (empty($this->getSlug())) {
			$this->setSlug($name);
		}
	}

	/**
	 * @return string
	 */
	public function getWebsite() {
		return $this->website;
	}

	/**
	 * @param string $website
	 */
	public function setWebsite($website) {
		$this->website = $website;
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @param string $email
	 */
	public function setEmail($email) {
		$this->email = $email;
	}

	/**
	 * @return string
	 */
	public function getExtraInfo() {
		return $this->extraInfo;
	}

	/**
	 * @param string $extraInfo
	 */
	public function setExtraInfo($extraInfo) {
		$this->extraInfo = $extraInfo;
	}

	/**
	 * @return User[]
	 */
	public function getUsers() {
		return $this->users;
	}

	/**
	 * @param User[] $users
	 */
	public function setUsers($users) {
		$this->users = $users;
	}

	/**
	 * @return ForeignBook[]
	 */
	public function getForeignBooks() {
		return $this->foreignBooks;
	}

	/**
	 * @param ForeignBook[] $foreignBooks
	 */
	public function setForeignBooks($foreignBooks) {
		$this->foreignBooks = $foreignBooks;
	}

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
			'extraInfo' => $this->getExtraInfo(),
		];
	}
}
