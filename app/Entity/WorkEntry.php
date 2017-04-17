<?php namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Eko\FeedBundle\Item\Writer\RoutedItemInterface;

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
class WorkEntry extends Entity implements RoutedItemInterface, \JsonSerializable {

	const STATUS_0 = 0;
	const STATUS_1 = 1;
	const STATUS_2 = 2;
	const STATUS_3 = 3;
	const STATUS_4 = 4;
	const STATUS_5 = 5;
	const STATUS_6 = 6;
	const STATUS_7 = 7;

	const TYPE_SINGLE_USER = 0;
	const TYPE_MULTI_USER = 1;

	private static $statuses = [
		self::STATUS_0 => 'Планира се',
		self::STATUS_1 => 'Сканира се',
		self::STATUS_2 => 'За корекция',
		self::STATUS_3 => 'Коригира се',
		self::STATUS_4 => 'Иска се SFB',
		self::STATUS_5 => 'Чака проверка',
		self::STATUS_6 => 'Проверен',
		self::STATUS_7 => 'За добавяне',
	];

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="App\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var int
	 * @ORM\Column(type="smallint")
	 */
	private $type;

	/**
	 * @var int
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $bibliomanId;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100)
	 */
	private $title;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $author;

	/**
	 * Year of publication on paper or in e-format
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $pubYear;

	/**
	 * Publisher of the book
	 * @var string
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $publisher;

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
	 * @var DateTime
	 * @ORM\Column(type="datetime")
	 */
	private $date;

	/**
	 * @var int
	 * @ORM\Column(type="smallint")
	 */
	private $status = 0;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $progress = 0;

	/**
	 * @var bool
	 * @ORM\Column(type="boolean")
	 */
	private $isFrozen = false;

	/**
	 * If set, the entry files will be available for the public at the given date.
	 * @var DateTime
	 * @ORM\Column(type="date", nullable=true)
	 */
	private $availableAt;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $tmpfiles;

	/**
	 * @var int
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $tfsize;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $uplfile;

	/**
	 * Every user gets an automatic e-mail if his entry reaches some predefined
	 * period without updates. Here we track the date of the most recent notification.
	 * @var DateTime
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $lastNotificationDate;

	/**
	 * A status managed and seen only from the adminstrator
	 * @var string
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	private $adminStatus;

	/**
	 * A comment managed and seen only from the adminstrator
	 * @var string
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $adminComment;

	/**
	 * @var DateTime
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $deletedAt;

	/**
	 * @var WorkContrib[]
	 * @ORM\OneToMany(targetEntity="WorkContrib", mappedBy="entry")
	 */
	private $contribs;

	/**
	 * @var Thread
	 * @ORM\OneToOne(targetEntity="Thread", inversedBy="workEntry")
	 */
	private $commentThread;

	public function __toString() {
		return $this->getTitle();
	}

	public function getId() { return $this->id; }

	public function setType($type) { $this->type = $type; }
	public function getType() { return $this->type; }

	public function isSingleUser() {
		return $this->type == self::TYPE_SINGLE_USER;
	}
	public function isMultiUser() {
		return $this->type == self::TYPE_MULTI_USER;
	}

	public function setBibliomanId($bibliomanId) { $this->bibliomanId = $bibliomanId; }
	public function getBibliomanId() { return $this->bibliomanId; }

	public function setTitle($title) { $this->title = $title; }
	public function getTitle() { return $this->title; }

	public function setAuthor($author) { $this->author = $author; }
	public function getAuthor() { return $this->author; }

	public function setPublisher($publisher) { $this->publisher = $publisher; }
	public function getPublisher() { return $this->publisher; }

	public function setPubYear($pubYear) { $this->pubYear = $pubYear; }
	public function getPubYear() { return $this->pubYear; }

	public function setUser($user) { $this->user = $user; }
	/** @return User */
	public function getUser() { return $this->user; }

	public function canShowFilesTo(User $user) {
		return $this->isAvailable() || $this->belongsTo($user) || $user->inGroup([User::GROUP_WORKROOM_MEMBER, User::GROUP_WORKROOM_SUPERVISOR, User::GROUP_WORKROOM_ADMIN]);
	}

	public function belongsTo(User $user) {
		return $this->user->getId() === $user->getId();
	}

	public function setComment($comment) { $this->comment = $comment; }
	public function getComment() { return $this->comment; }

	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }

	public function setStatus($status) { $this->status = $status; }
	public function getStatus() { return $this->status; }

	public function getStatusName() {
		return self::$statuses[$this->getStatus()];
	}

	public function setProgress($progress) { $this->progress = $progress; }
	public function getProgress() { return $this->progress; }

	public function setIsFrozen($isFrozen) { $this->isFrozen = $isFrozen; }
	public function getIsFrozen() { return $this->isFrozen; }
	public function isFrozen() { return $this->isFrozen; }

	public function getAvailableAt($format = null) {
		if ($format !== null && $this->availableAt instanceof DateTime) {
			return $this->availableAt->format($format);
		}
		return $this->availableAt;
	}
	public function setAvailableAt($availableAt) {
		if (!$availableAt instanceof DateTime) {
			if (is_numeric($availableAt)) {
				$availableAt .= '-01-01';
			}
			$availableAt = new DateTime($availableAt);
		}
		$this->availableAt = $availableAt;
	}

	public function isAvailable($date = null) {
		if ($date === null) {
			$date = new DateTime();
		} else if (!$date instanceof DateTime) {
			$date = new DateTime($date);
		}
		return $this->getAvailableAt() <= $date;
	}

	public function setTmpfiles($tmpfiles) { $this->tmpfiles = $tmpfiles; }
	public function getTmpfiles() { return $this->tmpfiles; }

	public function setTfsize($tfsize) { $this->tfsize = $tfsize; }
	public function getTfsize() { return $this->tfsize; }

	public function setUplfile($uplfile) { $this->uplfile = $uplfile; }
	public function getUplfile() { return $this->uplfile; }

	/**
	 * @param DateTime $date
	 */
	public function setLastNotificationDate($date) { $this->lastNotificationDate = $date; }
	public function getLastNotificationDate() { return $this->lastNotificationDate; }

	public function setAdminStatus($adminStatus) { $this->adminStatus = $adminStatus; }
	public function getAdminStatus() { return $this->adminStatus; }

	public function setAdminComment($adminComment) { $this->adminComment = $adminComment; }
	public function getAdminComment() { return $this->adminComment; }

	public function isNotifiedWithin($interval) {
		if ($this->getLastNotificationDate() === null) {
			return false;
		}
		return $this->getLastNotificationDate() > new DateTime("-$interval");
	}

	public function hasNotifiableStatus() {
		return !in_array($this->getStatus(), [
			self::STATUS_5,
			self::STATUS_6,
			self::STATUS_7,
		]);
	}

	public function setCommentThread(Thread $thread) {
		$this->commentThread = $thread;
		return $this;
	}
	public function getCommentThread() { return $this->commentThread; }
	public function getNbComments() {
		return $this->commentThread ? $this->commentThread->getNumComments() : 0;
	}

	public function getDeletedAt() { return $this->deletedAt; }

	/**
	 * @param DateTime $deletedAt
	 */
	public function setDeletedAt($deletedAt) { $this->deletedAt = $deletedAt; }
	public function delete() {
		$this->setDeletedAt(new DateTime);
	}
	public function isDeleted() {
		return $this->deletedAt !== null;
	}

	public function getAllContribs() {
		return $this->contribs;
	}

	/**
	 * Return all non-deleted contributions
	 * @return WorkContrib[]
	 */
	public function getContribs() {
		$contribs = [];
		foreach ($this->contribs as $contrib) {
			if (!$contrib->isDeleted()) {
				$contribs[] = $contrib;
			}
		}
		return $contribs;
	}

	public function getOpenContribs() {
		$openContribs = [];
		foreach ($this->getContribs() as $contrib) {
			if (!$contrib->isFinished()) {
				$openContribs[] = $contrib;
			}
		}
		return $openContribs;
	}

	public function hasOpenContribs() {
		return $this->getOpenContribs() > 0;
	}

	public function hasContribForUser(User $user) {
		foreach ($this->getContribs() as $contrib) {
			if ($contrib->belongsTo($user)) {
				return true;
			}
		}
		return false;
	}

	/** {@inheritdoc} */
	public function getFeedItemTitle() {
		return implode(' — ', array_filter([$this->getTitle(), $this->getAuthor()]));
	}

	/** {@inheritdoc} */
	public function getFeedItemDescription() {
		$comment = nl2br($this->getComment());
		return <<<DESC
$comment
<ul>
	<li>Заглавие: {$this->getTitle()}</li>
	<li>Автор: {$this->getAuthor()}</li>
	<li>Издател: {$this->getPublisher()}</li>
	<li>Година: {$this->getPubYear()}</li>
	<li>Отговорник: {$this->getUser()->getUsername()}</li>
	<li>Етап: {$this->getStatusName()}</li>
</ul>
DESC;
	}

	/** {@inheritdoc} */
	public function getFeedItemPubDate() {
		return $this->getDate();
	}

	/** {@inheritdoc} */
	public function getFeedItemRouteName() {
		return 'workroom_entry_edit';
	}

	/** {@inheritdoc} */
	public function getFeedItemRouteParameters() {
		return ['id' => $this->getId()];
	}

	/** {@inheritdoc} */
	public function getFeedItemUrlAnchor() {
		return '';
	}

	public function getFeedItemCreator() {
		return $this->getUser()->getUsername();
	}

	public function getFeedItemGuid() {
		return "chitanka-work-entry-{$this->getId()}-{$this->getStatus()}-{$this->getProgress()}";
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return array
	 */
	public function jsonSerialize() {
		$fields = [
			'id' => $this->getId(),
			'type' => $this->getType(),
			'bibliomanId' => $this->getBibliomanId(),
			'title' => $this->getTitle(),
			'author' => $this->getAuthor(),
			'publisher' => $this->getPublisher(),
			'pubYear' => $this->getPubYear(),
			'user' => $this->getUser(),
			'comment' => $this->getComment(),
			'date' => $this->getDate(),
			'status' => $this->getStatus(),
			'progress' => $this->getProgress(),
			'isFrozen' => $this->getIsFrozen(),
			'availableAt' => $this->getAvailableAt(),
			'deletedAt' => $this->getDeletedAt(),
			'contribs' => $this->getContribs(),
		];
		if ($this->isAvailable()) {
			$fields += [
				'file' => $this->getTmpfiles(),
				'filesize' => $this->getTfsize(),
			];
		}
		return $fields;
	}
}
