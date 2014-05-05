<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="book_link",
*	uniqueConstraints={@ORM\UniqueConstraint(name="book_site_uniq", columns={"book_id", "site_id"})}
* )
*/
class BookLink extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var Book
	 * @ORM\ManyToOne(targetEntity="Book", inversedBy="links")
	 */
	private $book;

	/**
	 * @var BookSite
	 * @ORM\ManyToOne(targetEntity="BookSite")
	 */
	private $site;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=50)
	 */
	private $code;

	public function getId() { return $this->id; }

	public function setBook($book) { $this->book = $book; }
	public function getBook() { return $this->book; }

	public function setSite($site) { $this->site = $site; }
	public function getSite() { return $this->site; }

	public function getSiteName() { return $this->site->getName(); }

	public function setCode($code) { $this->code = $code; }
	public function getCode() { return $this->code; }

	public function getUrl() {
		return str_replace('BOOKID', $this->code, $this->site->getUrl());
	}

	public function __toString() {
		return $this->getSite() .' ('.$this->code.')';
	}

}
