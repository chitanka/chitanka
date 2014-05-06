<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Entity\UserTextContribRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="user_text_contrib",
 *	indexes={
 *		@ORM\Index(name="date_idx", columns={"date"})}
 * )
 */
class UserTextContrib extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var User
	 * @ORM\ManyToOne(targetEntity="User")
	 */
	private $user;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100)
	 */
	private $username;

	/**
	 * @var Text
	 * @ORM\ManyToOne(targetEntity="Text", inversedBy="userContribs")
	 */
	private $text;

	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	private $size;

	/**
	 * @var int
	 * @ORM\Column(type="smallint")
	 */
	private $percent;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255)
	 */
	private $comment;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime")
	 */
	private $date;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=30, nullable=true)
	 */
	private $humandate;

	public function getId() { return $this->id; }

	public function getUser() { return $this->user; }
	public function setUser(User $user) {
		$this->user = $user;
		$this->setUsername($user->getUsername());
	}

	/**
	 * @param string $username
	 */
	public function setUsername($username) { $this->username = $username; }
	public function getUsername() { return $this->username; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	/**
	 * @param int $size
	 */
	public function setSize($size) { $this->size = $size; }
	public function getSize() { return $this->size; }

	public function setPercent($percent) { $this->percent = $percent; }
	public function getPercent() { return $this->percent; }

	public function setComment($comment) { $this->comment = $comment; }
	public function getComment() { return $this->comment; }

	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }

	public function setHumandate($humandate) { $this->humandate = $humandate; }
	public function getHumandate() { return $this->humandate; }

	public function __toString() {
		return sprintf('%s: %s (%s, %s%%)', $this->getComment(), $this->getUser(), $this->getHumandate(), $this->getPercent());
	}

	/** @ORM\PrePersist */
	public function preInsert() {
		$this->updateSize();
	}

	protected function updateSize() {
		$this->setSize($this->getText()->getSize() * $this->getPercent() / 100);
	}
}
