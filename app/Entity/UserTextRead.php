<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Entity\UserTextReadRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="user_text_read",
 *	uniqueConstraints={@ORM\UniqueConstraint(name="user_text_uniq", columns={"user_id", "text_id"})},
 *	indexes={
 *		@ORM\Index(name="text_idx", columns={"text_id"}),
 *		@ORM\Index(name="created_at_idx", columns={"created_at"})}
 * )
 * TODO replace this entity with a new "read" bookmark folder
 */
class UserTextRead extends Entity {
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
	 * @var Text
	 * @ORM\ManyToOne(targetEntity="Text")
	 */
	private $text;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime")
	 */
	private $createdAt;

	public function __construct(User $user, Text $text) {
		$this->setUser($user);
		$this->setText($text);
	}

	public function getId() { return $this->id; }

	/**
	 * @param User $user
	 */
	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	/**
	 * @param Text $text
	 */
	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	/**
	 * @param \DateTime $createdAt
	 */
	public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; }
	public function getCreatedAt() { return $this->createdAt; }

	/** @ORM\PrePersist */
	public function preInsert() {
		$this->setCreatedAt(new \DateTime);
	}
}
