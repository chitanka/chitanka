<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass="App\Entity\WorkEntryRepository")
* @ORM\Table(name="work_contrib",
*	uniqueConstraints={@ORM\UniqueConstraint(name="entry_user_uniq", columns={"entry_id", "user_id"})},
*	indexes={
*		@ORM\Index(name="user_idx", columns={"user_id"})}
* )
*/
class WorkContrib extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var WorkEntry
	 * @ORM\ManyToOne(targetEntity="WorkEntry", inversedBy="contribs")
	 */
	private $entry;

	/**
	 * @var User
	 * @ORM\ManyToOne(targetEntity="User")
	 */
	private $user;

	/**
	 * @var string
	 * @ORM\Column(type="text")
	 */
	private $comment;

	/**
	 * @var int
	 * @ORM\Column(type="smallint")
	 */
	private $progress = 0;

	/**
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private $isFrozen = false;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime")
	 */
	private $date;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $uplfile;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $filesize;

	/**
	 * @var \DateTime
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $deletedAt;

	public function getId() { return $this->id; }

	public function setEntry($entry) { $this->entry = $entry; }
	public function getEntry() { return $this->entry; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setComment($comment) { $this->comment = $comment; }
	public function getComment() { return $this->comment; }

	public function setProgress($progress) { $this->progress = $progress; }
	public function getProgress() { return $this->progress; }

	public function setIsFrozen($isFrozen) { $this->isFrozen = $isFrozen; }
	public function isFrozen() { return $this->isFrozen; }

	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }

	public function setUplfile($uplfile) { $this->uplfile = $uplfile; }
	public function getUplfile() { return $this->uplfile; }

	public function setFilesize($filesize) { $this->filesize = $filesize; }
	public function getFilesize() { return $this->filesize; }

	public function isFinished() {
		return $this->getProgress() == 100;
	}

	/**
	 * @param \DateTime $deletedAt
	 */
	public function setDeletedAt($deletedAt) { $this->deletedAt = $deletedAt; }
	public function isDeleted() { return $this->deletedAt !== null; }
	public function delete() {
		$this->setDeletedAt(new \DateTime);
	}
}
