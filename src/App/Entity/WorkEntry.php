<?php namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass="App\Entity\WorkEntryRepository")
* @ORM\Table(name="work_entry",
*	indexes={
*		@ORM\Index(name="title_idx", columns={"title"}),
*		@ORM\Index(name="author_idx", columns={"author"}),
*		@ORM\Index(name="status_idx", columns={"status"}),
*		@ORM\Index(name="date_idx", columns={"date"})}
* )
*/
class WorkEntry extends Entity {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var integer $type
	 * @ORM\Column(type="smallint")
	 */
	private $type;

	/**
	 * @var string $title
	 * @ORM\Column(type="string", length=100)
	 */
	private $title;

	/**
	 * @var string $author
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $author;

	/**
	 * Year of publication on paper or in e-format
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $pubYear;

	/**
	 * Publisher of the book
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $publisher;

	/**
	 * @var integer $user
	 * @ORM\ManyToOne(targetEntity="User")
	 */
	private $user;

	/**
	 * @var text $comment
	 * @ORM\Column(type="text")
	 */
	private $comment;

	/**
	 * @var datetime $date
	 * @ORM\Column(type="datetime")
	 */
	private $date;

	/**
	 * @var integer $status
	 * @ORM\Column(type="smallint")
	 */
	private $status = 0;

	/**
	 * @var integer $progress
	 * @ORM\Column(type="smallint")
	 */
	private $progress = 0;

	/**
	 * @var boolean $is_frozen
	 * @ORM\Column(type="boolean")
	 */
	private $is_frozen = false;

	/**
	 * @var string $tmpfiles
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $tmpfiles;

	/**
	 * @var integer $tfsize
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $tfsize;

	/**
	 * @var string $uplfile
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $uplfile;

	/**
	 * Every user gets an automatic e-mail if his entry reaches some predefined
	 * period without updates. Here we track the date of the most recent notification.
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $last_notification_date;

	/**
	 * A status managed and seen only from the adminstrator
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $admin_status;

	/**
	 * A comment managed and seen only from the adminstrator
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $admin_comment;

	/**
	 * @var datetime
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $deleted_at;

	/**
	 * @ORM\OneToMany(targetEntity="WorkContrib", mappedBy="entry")
	 */
	private $contribs;

	/**
	 * @ORM\OneToOne(targetEntity="Thread", inversedBy="workEntry")
	 */
	private $comment_thread;

	public function __toString() {
		return $this->getTitle();
	}

	public function getId() { return $this->id; }

	public function setType($type) { $this->type = $type; }
	public function getType() { return $this->type; }

	public function setTitle($title) { $this->title = $title; }
	public function getTitle() { return $this->title; }

	public function setAuthor($author) { $this->author = $author; }
	public function getAuthor() { return $this->author; }

	public function setPublisher($publisher) { $this->publisher = $publisher; }
	public function getPublisher() { return $this->publisher; }

	public function setPubYear($pubYear) { $this->pubYear = $pubYear; }
	public function getPubYear() { return $this->pubYear; }

	public function setUser($user) { $this->user = $user; }
	/** @return integer */
	public function getUser() { return $this->user; }

	public function setComment($comment) { $this->comment = $comment; }
	public function getComment() { return $this->comment; }

	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }

	public function setStatus($status) { $this->status = $status; }
	public function getStatus() { return $this->status; }

	public function setProgress($progress) { $this->progress = $progress; }
	public function getProgress() { return $this->progress; }

	public function setIsFrozen($isFrozen) { $this->is_frozen = $isFrozen; }
	public function getIsFrozen() { return $this->is_frozen; }

	public function setTmpfiles($tmpfiles) { $this->tmpfiles = $tmpfiles; }
	public function getTmpfiles() { return $this->tmpfiles; }

	public function setTfsize($tfsize) { $this->tfsize = $tfsize; }
	public function getTfsize() { return $this->tfsize; }

	public function setUplfile($uplfile) { $this->uplfile = $uplfile; }
	public function getUplfile() { return $this->uplfile; }

	/**
	 * @param \DateTime $date
	 */
	public function setLastNotificationDate($date) { $this->last_notification_date = $date; }
	public function getLastNotificationDate() { return $this->last_notification_date; }

	public function setAdminStatus($admin_status) { $this->admin_status = $admin_status; }
	public function getAdminStatus() { return $this->admin_status; }

	public function setAdminComment($admin_comment) { $this->admin_comment = $admin_comment; }
	public function getAdminComment() { return $this->admin_comment; }

	public function isNotifiedWithin($interval) {
		if ($this->getLastNotificationDate() === null) {
			return false;
		}
		return $this->getLastNotificationDate() > new \DateTime("-$interval");
	}

	public function setCommentThread(Thread $thread) {
		$this->comment_thread = $thread;
		return $this;
	}
	public function getCommentThread() { return $this->comment_thread; }

	public function getDeletedAt() { return $this->deleted_at; }

	/**
	 * @param \DateTime $deleted_at
	 */
	public function setDeletedAt($deleted_at) { $this->deleted_at = $deleted_at; }
	public function delete() {
		$this->setDeletedAt(new \DateTime);
	}
	public function isDeleted() {
		return $this->deleted_at !== null;
	}

	public function getContribs() { return $this->contribs; }

	public function getOpenContribs() {
		$openContribs = array();
		foreach ($this->getContribs() as $contrib/*@var $contrib WorkContrib*/) {
			if ( ! $contrib->isFinished() && ! $contrib->isDeleted()) {
				$openContribs[] = $contrib;
			}
		}
		return $openContribs;
	}
}
