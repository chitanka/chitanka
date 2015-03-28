<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass="App\Entity\BookmarkRepository")
* @ORM\HasLifecycleCallbacks
* @ORM\Table(name="bookmark",
*	uniqueConstraints={@ORM\UniqueConstraint(name="uniq_key", columns={"folder_id", "text_id", "user_id"})}
* )
*/
class Bookmark extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var BookmarkFolder
	 * @ORM\ManyToOne(targetEntity="BookmarkFolder")
	 */
	private $folder;

	/**
	 * @var Text
	 * @ORM\ManyToOne(targetEntity="Text")
	 */
	private $text;

	/**
	 * @var User
	 * @ORM\ManyToOne(targetEntity="User", inversedBy="bookmarks")
	 */
	private $user;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime")
	 */
	private $createdAt;

	public function __construct($fields) {
		foreach (['folder', 'text', 'user'] as $field) {
			if (isset($fields[$field])) {
				$setter = 'set' . ucfirst($field);
				$this->$setter($fields[$field]);
			}
		}
	}

	public function getId() { return $this->id; }

	public function setFolder($folder) { $this->folder = $folder; }
	public function getFolder() { return $this->folder; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

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
