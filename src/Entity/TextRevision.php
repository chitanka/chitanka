<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Entity\TextRevisionRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Table(name="text_revision",
 *	indexes={
 *		@ORM\Index(name="text_idx", columns={"text_id"}),
 *		@ORM\Index(name="user_idx", columns={"user_id"}),
 *		@ORM\Index(name="date_idx", columns={"date"})}
 * )
 */
class TextRevision extends Entity implements \JsonSerializable {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var Text
	 * @ORM\ManyToOne(targetEntity="Text", inversedBy="revisions")
	 */
	private $text;

	/**
	 * @var User
	 * @ORM\ManyToOne(targetEntity="User")
	 */
	private $user;

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
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private $first = true;

	public function getId() { return $this->id; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setComment($comment) { $this->comment = $comment; }
	public function getComment() { return $this->comment; }

	/**
	 * @param \DateTime $date
	 */
	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }

	/**
	 * @param bool $first
	 */
	public function setFirst($first) { $this->first = $first; }
	public function getFirst() { return $this->first; }

	public function __toString() {
		return sprintf('%s (%s, %s)', $this->getComment(), $this->getUser(), $this->getDate()->format('Y-m-d H:i:s'));
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'text' => $this->getText(),
			'comment' => $this->getComment(),
			'date' => $this->getDate(),
			'isFirst' => $this->getFirst(),
		];
	}
}
